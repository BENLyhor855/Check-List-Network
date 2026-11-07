// Package api provides HTTP routing and transport for the foundation modules.
package api

import (
	"database/sql"
	"net/http"
	"racnoc/internal/inventory"
	"sync"
	"time"
)

type Server struct {
	DB        *sql.DB
	Secure    bool
	Origin    string
	inventory inventory.Store
	mu        sync.Mutex
	attempts  map[string][]time.Time
}

func New(db *sql.DB, secure bool, origin string) *Server {
	return &Server{DB: db, Secure: secure, Origin: origin, inventory: inventory.Store{DB: db}, attempts: make(map[string][]time.Time)}
}
func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("GET /health/live", s.live)
	mux.HandleFunc("GET /health/ready", s.ready)
	mux.HandleFunc("POST /api/v1/auth/login", s.login)
	mux.HandleFunc("POST /api/v1/auth/logout", s.logout)
	mux.HandleFunc("GET /api/v1/auth/me", s.me)
	mux.HandleFunc("GET /api/v1/users", s.users)
	mux.HandleFunc("POST /api/v1/users", s.users)
	mux.HandleFunc("PATCH /api/v1/users/{id}", s.users)
	for _, resource := range []string{"buildings", "locations", "devices", "audit"} {
		mux.HandleFunc("/api/v1/"+resource, s.protect(resource))
	}
	return s.middleware(mux)
}
