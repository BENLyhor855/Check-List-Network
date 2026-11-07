package main

import (
	"context"

	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"racnoc/internal/api"
	"racnoc/internal/auth"
	"racnoc/internal/config"
	"racnoc/internal/database"

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
	cfg := config.Load()
	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	db, e := database.Open(ctx, cfg)
	if e != nil {
		return e
	}
	defer db.Close()
	if len(os.Args) > 1 && os.Args[1] == "migrate" {
		return database.Migrate(ctx, db)
	}
	if len(os.Args) > 1 && os.Args[1] == "bootstrap" {
		return auth.Bootstrap(ctx, db, cfg.BootstrapEmail, cfg.BootstrapPassword)
	}
	origin := cfg.Origin
	srv := http.Server{Addr: cfg.ListenAddress, Handler: api.New(db, cfg.CookieSecure, origin).Handler(), ReadHeaderTimeout: 5 * time.Second, ReadTimeout: 15 * time.Second, WriteTimeout: 15 * time.Second, IdleTimeout: 60 * time.Second, MaxHeaderBytes: 16384}
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
