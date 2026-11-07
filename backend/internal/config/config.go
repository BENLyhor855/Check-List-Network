// Package config is the only place that reads process environment settings.
package config

import "os"

type Config struct {
	DBUser, DBPassword, DBAddress, DBName string
	ListenAddress, Origin                 string
	CookieSecure                          bool
	BootstrapEmail, BootstrapPassword     string
}

func Load() Config {
	return Config{
		DBUser: os.Getenv("DB_USER"), DBPassword: os.Getenv("DB_PASSWORD"), DBAddress: value("DB_ADDRESS", "127.0.0.1:3306"), DBName: value("DB_NAME", "racnoc"),
		ListenAddress: value("LISTEN_ADDRESS", "127.0.0.1:8080"), Origin: value("APP_ORIGIN", "https://localhost"), CookieSecure: os.Getenv("COOKIE_SECURE") != "false",
		BootstrapEmail: os.Getenv("BOOTSTRAP_EMAIL"), BootstrapPassword: os.Getenv("BOOTSTRAP_PASSWORD"),
	}
}
func value(k, d string) string {
	if v := os.Getenv(k); v != "" {
		return v
	}
	return d
}
