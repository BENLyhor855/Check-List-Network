package api

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"encoding/hex"
	"golang.org/x/crypto/bcrypt"
	"net"
	"net/http"
	"racnoc/internal/audit"
	"strings"
	"time"
)

type principal struct {
	ID    int64  `json:"id"`
	Email string `json:"email"`
	Role  string `json:"role"`
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
		e = audit.Record(r.Context(), tx, id, "auth.login", "session")
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

func (s *Server) logout(w http.ResponseWriter, r *http.Request) {
	if c, e := r.Cookie("rac_session"); e == nil {
		if _, e = s.DB.ExecContext(r.Context(), "DELETE FROM sessions WHERE token_hash=?", hash(c.Value)); e != nil {
			fail(w, 500, "logout failed")
			return
		}
	}
	http.SetCookie(w, &http.Cookie{Name: "rac_session", Value: "", Path: "/", MaxAge: -1, HttpOnly: true, Secure: s.Secure, SameSite: http.SameSiteStrictMode})
	w.WriteHeader(204)
}
func (s *Server) me(w http.ResponseWriter, r *http.Request) {
	p, e := s.user(r)
	if e != nil {
		fail(w, 401, "authentication required")
		return
	}
	write(w, 200, p)
}
