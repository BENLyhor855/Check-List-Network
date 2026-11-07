package api

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"net"
	"net/http"
	"net/mail"
	"strings"
	"sync"
	"time"

	"golang.org/x/crypto/bcrypt"
)

type Server struct {
	DB       *sql.DB
	Secure   bool
	Origin   string
	mu       sync.Mutex
	attempts map[string][]time.Time
}
type principal struct {
	ID    int64  `json:"id"`
	Email string `json:"email"`
	Role  string `json:"role"`
}

func New(db *sql.DB, secure bool, origin string) *Server {
	return &Server{DB: db, Secure: secure, Origin: origin, attempts: make(map[string][]time.Time)}
}
func write(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(v)
}
func fail(w http.ResponseWriter, status int, msg string) {
	write(w, status, map[string]string{"error": msg})
}
func decode(w http.ResponseWriter, r *http.Request, v any) bool {
	if !strings.HasPrefix(r.Header.Get("Content-Type"), "application/json") {
		fail(w, 415, "application/json required")
		return false
	}
	d := json.NewDecoder(http.MaxBytesReader(w, r.Body, 65536))
	d.DisallowUnknownFields()
	if d.Decode(v) != nil || d.Decode(new(any)) != io.EOF {
		fail(w, 400, "invalid JSON request")
		return false
	}
	return true
}
func hash(s string) string { h := sha256.Sum256([]byte(s)); return hex.EncodeToString(h[:]) }
func (s *Server) user(r *http.Request) (principal, error) {
	var p principal
	c, e := r.Cookie("rac_session")
	if e != nil {
		return p, e
	}
	e = s.DB.QueryRowContext(r.Context(), `SELECT u.id,u.email,r.name FROM sessions s JOIN users u ON u.id=s.user_id JOIN roles r ON r.id=u.role_id WHERE s.token_hash=? AND s.expires_at>UTC_TIMESTAMP() AND u.active=TRUE`, hash(c.Value)).Scan(&p.ID, &p.Email, &p.Role)
	return p, e
}
func (s *Server) permitted(ctx context.Context, p principal, permission string) bool {
	var n int
	e := s.DB.QueryRowContext(ctx, `SELECT COUNT(*) FROM users u JOIN role_permissions rp ON rp.role_id=u.role_id JOIN permissions p ON p.id=rp.permission_id WHERE u.id=? AND p.name=?`, p.ID, permission).Scan(&n)
	return e == nil && n > 0
}
func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("GET /api/v1/users", s.users)
	mux.HandleFunc("POST /api/v1/users", s.users)
	mux.HandleFunc("PATCH /api/v1/users/{id}", s.users)
	mux.HandleFunc("GET /health/live", func(w http.ResponseWriter, r *http.Request) { write(w, 200, map[string]string{"status": "alive"}) })
	mux.HandleFunc("GET /health/ready", func(w http.ResponseWriter, r *http.Request) {
		if s.DB.PingContext(r.Context()) != nil {
			fail(w, 503, "database unavailable")
			return
		}
		write(w, 200, map[string]string{"status": "ready"})
	})
	mux.HandleFunc("POST /api/v1/auth/login", s.login)
	mux.HandleFunc("POST /api/v1/auth/logout", func(w http.ResponseWriter, r *http.Request) {
		if c, e := r.Cookie("rac_session"); e == nil {
			if _, e = s.DB.ExecContext(r.Context(), "DELETE FROM sessions WHERE token_hash=?", hash(c.Value)); e != nil {
				fail(w, 500, "logout failed")
				return
			}
		}
		http.SetCookie(w, &http.Cookie{Name: "rac_session", Value: "", Path: "/", MaxAge: -1, HttpOnly: true, Secure: s.Secure, SameSite: http.SameSiteStrictMode})
		w.WriteHeader(204)
	})
	mux.HandleFunc("GET /api/v1/auth/me", func(w http.ResponseWriter, r *http.Request) {
		p, e := s.user(r)
		if e != nil {
			fail(w, 401, "authentication required")
			return
		}
		write(w, 200, p)
	})
	for _, resource := range []string{"buildings", "locations", "devices", "audit"} {
		mux.HandleFunc("/api/v1/"+resource, s.protect(resource))
	}
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("Cache-Control", "no-store")
		if r.Method != "GET" && r.Method != "HEAD" && r.Header.Get("Origin") != s.Origin {
			fail(w, 403, "same-origin request required")
			return
		}
		ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
		defer cancel()
		mux.ServeHTTP(w, r.WithContext(ctx))
	})
}
func (s *Server) login(w http.ResponseWriter, r *http.Request) {
	host, _, _ := net.SplitHostPort(r.RemoteAddr)
	now := time.Now()
	s.mu.Lock()
	// Bound memory and expire idle buckets; trust only the socket peer, not forwarded headers.
	for k, v := range s.attempts {
		if len(v) == 0 || now.Sub(v[len(v)-1]) > time.Minute {
			delete(s.attempts, k)
		}
	}
	times := s.attempts[host]
	fresh := times[:0]
	for _, t := range times {
		if now.Sub(t) < time.Minute {
			fresh = append(fresh, t)
		}
	}
	if len(fresh) >= 10 || len(s.attempts) >= 10000 {
		s.mu.Unlock()
		fail(w, 429, "try again later")
		return
	}
	s.attempts[host] = append(fresh, now)
	s.mu.Unlock()
	var in struct {
		Email    string `json:"email"`
		Password string `json:"password"`
	}
	if !decode(w, r, &in) {
		return
	}
	var id int64
	var password string
	e := s.DB.QueryRowContext(r.Context(), "SELECT id,password_hash FROM users WHERE email=? AND active=TRUE", strings.ToLower(strings.TrimSpace(in.Email))).Scan(&id, &password)
	if e != nil || bcrypt.CompareHashAndPassword([]byte(password), []byte(in.Password)) != nil { // Dummy work makes unknown users less distinguishable.
		if e != nil {
			bcrypt.CompareHashAndPassword([]byte("$2a$12$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW"), []byte(in.Password))
		}
		fail(w, 401, "invalid credentials")
		return
	}
	b := make([]byte, 32)
	if _, e = rand.Read(b); e != nil {
		fail(w, 500, "session unavailable")
		return
	}
	token := hex.EncodeToString(b)
	tx, e := s.DB.BeginTx(r.Context(), nil)
	if e != nil {
		fail(w, 500, "session unavailable")
		return
	}
	defer tx.Rollback()
	_, e = tx.ExecContext(r.Context(), "INSERT INTO sessions(token_hash,user_id,expires_at) VALUES(?,?,?)", hash(token), id, now.UTC().Add(8*time.Hour))
	if e == nil {
		_, e = tx.ExecContext(r.Context(), "INSERT INTO audit_logs(user_id,action,target,result) VALUES(?,'auth.login','session','success')", id)
	}
	if e == nil {
		e = tx.Commit()
	}
	if e != nil {
		fail(w, 500, "session unavailable")
		return
	}
	http.SetCookie(w, &http.Cookie{Name: "rac_session", Value: token, Path: "/", HttpOnly: true, Secure: s.Secure, SameSite: http.SameSiteStrictMode, MaxAge: 28800})
	write(w, 200, map[string]string{"status": "authenticated"})
}
func (s *Server) protect(resource string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		p, e := s.user(r)
		if e != nil {
			fail(w, 401, "authentication required")
			return
		}
		permission := "inventory.read"
		if resource == "audit" {
			permission = "audit.read"
		}
		if r.Method == "POST" {
			permission = "inventory.write"
		}
		if !s.permitted(r.Context(), p, permission) {
			fail(w, 403, "permission denied")
			return
		}
		switch r.Method {
		case "GET":
			s.list(w, r, resource)
		case "POST":
			if resource == "audit" {
				fail(w, 405, "read only")
				return
			}
			s.create(w, r, p, resource)
		default:
			fail(w, 405, "method not allowed")
		}
	}
}
func (s *Server) list(w http.ResponseWriter, r *http.Request, resource string) {
	queries := map[string]string{"buildings": "SELECT id,name FROM buildings ORDER BY id LIMIT 100 OFFSET ?", "locations": "SELECT id,building_id,name FROM locations ORDER BY id LIMIT 100 OFFSET ?", "devices": "SELECT d.id,d.asset_id,d.name,d.manufacturer,d.model,d.management_ip,d.category,d.status,l.name location,b.name building FROM devices d JOIN locations l ON l.id=d.location_id JOIN buildings b ON b.id=l.building_id ORDER BY d.id LIMIT 100 OFFSET ?", "audit": "SELECT id,user_id,action,target,result,created_at FROM audit_logs ORDER BY id DESC LIMIT 100 OFFSET ?"}
	offset := 0
	if v := r.URL.Query().Get("offset"); v != "" {
		if _, e := fmt.Sscanf(v, "%d", &offset); e != nil || offset < 0 || offset > 1000000 {
			fail(w, 400, "invalid offset")
			return
		}
	}
	rows, e := s.DB.QueryContext(r.Context(), queries[resource], offset)
	if e != nil {
		fail(w, 500, "query failed")
		return
	}
	defer rows.Close()
	cols, _ := rows.Columns()
	items := []map[string]any{}
	for rows.Next() {
		values := make([]any, len(cols))
		ptr := make([]any, len(cols))
		for i := range values {
			ptr[i] = &values[i]
		}
		if rows.Scan(ptr...) != nil {
			fail(w, 500, "query failed")
			return
		}
		item := map[string]any{}
		for i, k := range cols {
			if b, ok := values[i].([]byte); ok {
				item[k] = string(b)
			} else {
				item[k] = values[i]
			}
		}
		items = append(items, item)
	}
	if rows.Err() != nil {
		fail(w, 500, "query failed")
		return
	}
	write(w, 200, map[string]any{"items": items, "limit": 100, "offset": offset})
}
func (s *Server) create(w http.ResponseWriter, r *http.Request, p principal, resource string) {
	var in struct {
		Name         string `json:"name"`
		BuildingID   int64  `json:"building_id"`
		LocationID   int64  `json:"location_id"`
		AssetID      string `json:"asset_id"`
		Manufacturer string `json:"manufacturer"`
		Model        string `json:"model"`
		SerialNumber string `json:"serial_number"`
		ManagementIP string `json:"management_ip"`
		Category     string `json:"category"`
		Notes        string `json:"notes"`
	}
	if !decode(w, r, &in) {
		return
	}
	in.Name = strings.TrimSpace(in.Name)
	if in.Name == "" || len(in.Name) > 120 {
		fail(w, 400, "name required, maximum 120 bytes")
		return
	}
	var query string
	var args []any
	switch resource {
	case "buildings":
		query = "INSERT INTO buildings(name) VALUES(?)"
		args = []any{in.Name}
	case "locations":
		if in.BuildingID < 1 {
			fail(w, 400, "building required")
			return
		}
		query = "INSERT INTO locations(name,building_id) VALUES(?,?)"
		args = []any{in.Name, in.BuildingID}
	case "devices":
		if strings.TrimSpace(in.AssetID) == "" || len(in.AssetID) > 80 || in.LocationID < 1 || in.Category == "" || len(in.Category) > 50 || len(in.Manufacturer) > 80 || len(in.Model) > 80 || len(in.SerialNumber) > 120 || (in.ManagementIP != "" && net.ParseIP(in.ManagementIP) == nil) {
			fail(w, 400, "valid asset ID, location, category and management IP required")
			return
		}
		query = "INSERT INTO devices(name,asset_id,manufacturer,model,serial_number,management_ip,category,location_id,notes) VALUES(?,?,?,?,?,?,?,?,?)"
		args = []any{in.Name, in.AssetID, in.Manufacturer, in.Model, in.SerialNumber, in.ManagementIP, in.Category, in.LocationID, in.Notes}
	}
	tx, e := s.DB.BeginTx(r.Context(), nil)
	if e != nil {
		fail(w, 500, "database unavailable")
		return
	}
	defer tx.Rollback()
	result, e := tx.ExecContext(r.Context(), query, args...)
	if e != nil {
		fail(w, 409, "record conflicts or referenced location/building does not exist")
		return
	}
	id, e := result.LastInsertId()
	if e == nil {
		_, e = tx.ExecContext(r.Context(), "INSERT INTO audit_logs(user_id,action,target,result) VALUES(?,?,?,'success')", p.ID, resource+".create", fmt.Sprint(id))
	}
	if e == nil {
		e = tx.Commit()
	}
	if e != nil {
		slog.Error("inventory transaction failed")
		fail(w, 500, "transaction failed")
		return
	}
	write(w, 201, map[string]int64{"id": id})
}
func Bootstrap(ctx context.Context, db *sql.DB, email, password string) error {
	var count int
	if e := db.QueryRowContext(ctx, "SELECT COUNT(*) FROM users").Scan(&count); e != nil {
		return e
	}
	if count > 0 {
		return nil
	}
	address, e := mail.ParseAddress(email)
	if e != nil || address.Address != email || len(password) < 14 || len(password) > 72 {
		return errors.New("first startup requires BOOTSTRAP_EMAIL and BOOTSTRAP_PASSWORD (14–72 bytes)")
	}
	digest, e := bcrypt.GenerateFromPassword([]byte(password), 12)
	if e != nil {
		return e
	}
	_, e = db.ExecContext(ctx, "INSERT INTO users(email,password_hash,role_id) VALUES(?,?,1)", strings.ToLower(email), string(digest))
	return e
}
