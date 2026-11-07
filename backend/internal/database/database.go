package database

import (
	"context"
	"database/sql"
	"fmt"
	"github.com/go-sql-driver/mysql"
	"racnoc/internal/config"
	"time"
)

func Open(ctx context.Context, c config.Config) (*sql.DB, error) {
	cfg := mysql.NewConfig()
	cfg.User = c.DBUser
	cfg.Passwd = c.DBPassword
	cfg.Net = "tcp"
	cfg.Addr = c.DBAddress
	cfg.DBName = c.DBName
	cfg.ParseTime = true
	cfg.Timeout = 5 * time.Second
	db, err := sql.Open("mysql", cfg.FormatDSN())
	if err != nil {
		return nil, err
	}
	db.SetMaxOpenConns(10)
	db.SetConnMaxLifetime(5 * time.Minute)
	if err = db.PingContext(ctx); err != nil {
		db.Close()
		return nil, fmt.Errorf("database not ready: %w", err)
	}
	return db, nil
}
