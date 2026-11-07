package api

import (
	"context"
	"net/http"
	"time"
)

func (s *Server) middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("Cache-Control", "no-store")
		if r.Method != "GET" && r.Method != "HEAD" && r.Header.Get("Origin") != s.Origin {
			fail(w, 403, "same-origin request required")
			return
		}
		ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
		defer cancel()
		next.ServeHTTP(w, r.WithContext(ctx))
	})
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
