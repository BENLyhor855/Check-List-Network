package database

import (
	"context"
	"database/sql"
	"fmt"
	"racnoc/migrations"
	"strings"
)

func Migrate(ctx context.Context, db *sql.DB) error {
	conn, e := db.Conn(ctx)
	if e != nil {
		return e
	}
	defer conn.Close()
	var lock int
	if e = conn.QueryRowContext(ctx, "SELECT GET_LOCK('racnoc_migrations',30)").Scan(&lock); e != nil || lock != 1 {
		return fmt.Errorf("migration lock unavailable")
	}
	defer conn.ExecContext(context.Background(), "SELECT RELEASE_LOCK('racnoc_migrations')")
	if _, e = conn.ExecContext(ctx, "CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(120) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)"); e != nil {
		return e
	}
	entries, e := migrations.Files.ReadDir(".")
	if e != nil {
		return e
	}
	for _, entry := range entries {
		if !strings.HasSuffix(entry.Name(), ".sql") {
			continue
		}
		var n int
		if e = conn.QueryRowContext(ctx, "SELECT COUNT(*) FROM schema_migrations WHERE version=?", entry.Name()).Scan(&n); e != nil {
			return e
		}
		if n > 0 {
			continue
		}
		body, _ := migrations.Files.ReadFile(entry.Name())
		for _, statement := range strings.Split(string(body), ";") {
			if strings.TrimSpace(statement) == "" {
				continue
			}
			if _, e = conn.ExecContext(ctx, statement); e != nil {
				return fmt.Errorf("migration %s failed; inspect partial DDL before retry: %w", entry.Name(), e)
			}
		}
		if _, e = conn.ExecContext(ctx, "INSERT INTO schema_migrations(version) VALUES(?)", entry.Name()); e != nil {
			return e
		}
	}
	return nil
}
