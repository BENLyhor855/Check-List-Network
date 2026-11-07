package api

import (
	"golang.org/x/crypto/bcrypt"
	"net/http"
	"net/mail"
	"strconv"
	"strings"
)

func (s *Server) users(w http.ResponseWriter, r *http.Request) {
	p, e := s.user(r)
	if e != nil {
		fail(w, 401, "authentication required")
		return
	}
	if !s.permitted(r.Context(), p, "users.manage") {
		fail(w, 403, "permission denied")
		return
	}
	if r.Method == "GET" {
		rows, e := s.DB.QueryContext(r.Context(), "SELECT u.id,u.email,r.name,u.active FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id LIMIT 100")
		if e != nil {
			fail(w, 500, "query failed")
			return
		}
		defer rows.Close()
		items := []map[string]any{}
		for rows.Next() {
			var id int64
			var email, role string
			var active bool
			if rows.Scan(&id, &email, &role, &active) != nil {
				fail(w, 500, "query failed")
				return
			}
			items = append(items, map[string]any{"id": id, "email": email, "role": role, "active": active})
		}
		if rows.Err() != nil {
			fail(w, 500, "query failed")
			return
		}
		write(w, 200, map[string]any{"items": items})
		return
	}
	var in struct {
		Email    string `json:"email"`
		Password string `json:"password"`
		RoleID   int64  `json:"role_id"`
		Active   bool   `json:"active"`
	}
	if !decode(w, r, &in) {
		return
	}
	if in.RoleID < 1 || in.RoleID > 6 {
		fail(w, 400, "role_id must be 1 through 6")
		return
	}
	tx, e := s.DB.BeginTx(r.Context(), nil)
	if e != nil {
		fail(w, 500, "database unavailable")
		return
	}
	defer tx.Rollback()
	var target int64
	switch r.Method {
	case "POST":
		a, e := mail.ParseAddress(in.Email)
		if e != nil || a.Address != in.Email || len(in.Email) > 254 || len(in.Password) < 14 || len(in.Password) > 72 {
			fail(w, 400, "valid email and 14–72 byte password required")
			return
		}
		digest, e := bcrypt.GenerateFromPassword([]byte(in.Password), 12)
		if e != nil {
			fail(w, 500, "password hashing failed")
			return
		}
		result, e := tx.ExecContext(r.Context(), "INSERT INTO users(email,password_hash,role_id,active) VALUES(?,?,?,TRUE)", strings.ToLower(in.Email), string(digest), in.RoleID)
		if e != nil {
			fail(w, 409, "email conflicts")
			return
		}
		target, _ = result.LastInsertId()
	case "PATCH":
		target, e = strconv.ParseInt(r.PathValue("id"), 10, 64)
		if e != nil || target < 1 || target == p.ID {
			fail(w, 400, "valid other user ID required")
			return
		}
		result, e := tx.ExecContext(r.Context(), "UPDATE users SET role_id=?,active=? WHERE id=?", in.RoleID, in.Active, target)
		if e != nil {
			fail(w, 500, "update failed")
			return
		}
		n, _ := result.RowsAffected()
		if n == 0 {
			fail(w, 404, "user missing or unchanged")
			return
		}
		if _, e = tx.ExecContext(r.Context(), "DELETE FROM sessions WHERE user_id=?", target); e != nil {
			fail(w, 500, "session revocation failed")
			return
		}
	default:
		fail(w, 405, "method not allowed")
		return
	}
	if _, e = tx.ExecContext(r.Context(), "INSERT INTO audit_logs(user_id,action,target,result) VALUES(?,?,?,'success')", p.ID, "users."+strings.ToLower(r.Method), strconv.FormatInt(target, 10)); e != nil {
		fail(w, 500, "audit failed")
		return
	}
	if e = tx.Commit(); e != nil {
		fail(w, 500, "transaction failed")
		return
	}
	code := 200
	if r.Method == "POST" {
		code = 201
	}
	write(w, code, map[string]int64{"id": target})
}
