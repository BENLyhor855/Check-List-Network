package main

import (
	"context"
	"database/sql"
	"fmt"
	"github.com/go-sql-driver/mysql"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"racnoc/internal/api"
	"racnoc/migrations"
	"strings"
	"syscall"
	"time"
)

func main() {
	if e := run(); e != nil {
		slog.Error("startup failed", "error", e)
		os.Exit(1)
	}
}
func run() error {
	cfg := mysql.NewConfig()
	cfg.User = os.Getenv("DB_USER")
	cfg.Passwd = os.Getenv("DB_PASSWORD")
	cfg.Net = "tcp"
	cfg.Addr = value("DB_ADDRESS", "127.0.0.1:3306")
	cfg.DBName = value("DB_NAME", "racnoc")
	cfg.ParseTime = true
	cfg.Timeout = 5 * time.Second
	db, e := sql.Open("mysql", cfg.FormatDSN())
	if e != nil {
		return e
	}
	defer db.Close()
	db.SetMaxOpenConns(10)
	db.SetConnMaxLifetime(5 * time.Minute)
	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	if e = db.PingContext(ctx); e != nil {
		return fmt.Errorf("database not ready: %w", e)
	}
	if len(os.Args) > 1 && os.Args[1] == "migrate" {
		return migrate(ctx, db)
	}
	if len(os.Args) > 1 && os.Args[1] == "bootstrap" {
		return api.Bootstrap(ctx, db, os.Getenv("BOOTSTRAP_EMAIL"), os.Getenv("BOOTSTRAP_PASSWORD"))
	}
	origin := value("APP_ORIGIN", "https://localhost")
	srv := http.Server{Addr: value("LISTEN_ADDRESS", "127.0.0.1:8080"), Handler: api.New(db, os.Getenv("COOKIE_SECURE") != "false", origin).Handler(), ReadHeaderTimeout: 5 * time.Second, ReadTimeout: 15 * time.Second, WriteTimeout: 15 * time.Second, IdleTimeout: 60 * time.Second, MaxHeaderBytes: 16384}
	stop := make(chan os.Signal, 1)
	signal.Notify(stop, syscall.SIGTERM, syscall.SIGINT)
	defer signal.Stop(stop)
	go func() {
		<-stop
		c, cancel := context.WithTimeout(context.Background(), 10*time.Second)
		defer cancel()
		srv.Shutdown(c)
	}()
	slog.Info("API starting", "address", srv.Addr)
	e = srv.ListenAndServe()
	if e == http.ErrServerClosed {
		return nil
	}
	return e
}
func value(k, d string) string {
	if v := os.Getenv(k); v != "" {
		return v
	}
	return d
}
func migrate(ctx context.Context, db *sql.DB) error {
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
