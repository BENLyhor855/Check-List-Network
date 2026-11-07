package auth

import (
	"context"
	"database/sql"
	"errors"
	"golang.org/x/crypto/bcrypt"
	"net/mail"
	"strings"
)

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
