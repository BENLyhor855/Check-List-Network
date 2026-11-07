package api

import (
	"errors"
	"log/slog"
	"net/http"
	"racnoc/internal/inventory"
	"strconv"
)

func parseOffset(value string) (int, error) {
	if value == "" {
		return 0, nil
	}
	n, err := strconv.Atoi(value)
	if err != nil || n < 0 || n > 1000000 {
		return 0, errors.New("invalid offset")
	}
	return n, nil
}
func (s *Server) list(w http.ResponseWriter, r *http.Request, resource string) {
	offset, err := parseOffset(r.URL.Query().Get("offset"))
	if err != nil {
		fail(w, 400, err.Error())
		return
	}
	items, err := s.inventory.List(r.Context(), resource, offset)
	if err != nil {
		fail(w, 500, "query failed")
		return
	}
	write(w, 200, map[string]any{"items": items, "limit": inventory.PageSize, "offset": offset})
}
func (s *Server) create(w http.ResponseWriter, r *http.Request, p principal, resource string) {
	var in inventory.Input
	if !decode(w, r, &in) {
		return
	}
	id, err := s.inventory.Create(r.Context(), p.ID, resource, in)
	if err != nil {
		var validation *inventory.ValidationError
		switch {
		case errors.As(err, &validation):
			fail(w, 400, validation.Message)
		case errors.Is(err, inventory.ErrConflict):
			fail(w, 409, inventory.ErrConflict.Error())
		default:
			slog.Error("inventory transaction failed")
			fail(w, 500, "transaction failed")
		}
		return
	}
	write(w, 201, map[string]int64{"id": id})
}
