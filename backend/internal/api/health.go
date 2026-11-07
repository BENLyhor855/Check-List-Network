package api

import "net/http"

func (s *Server) live(w http.ResponseWriter, r *http.Request) {
	write(w, http.StatusOK, map[string]string{"status": "alive"})
}
func (s *Server) ready(w http.ResponseWriter, r *http.Request) {
	if s.DB.PingContext(r.Context()) != nil {
		fail(w, http.StatusServiceUnavailable, "database unavailable")
		return
	}
	write(w, http.StatusOK, map[string]string{"status": "ready"})
}
