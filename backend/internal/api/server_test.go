package api

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"

	"github.com/go-sql-driver/mysql"
)

func TestLiveAndOrigin(t *testing.T) {
	h := New(nil, false, "http://localhost:4200").Handler()
	r := httptest.NewRecorder()
	h.ServeHTTP(r, httptest.NewRequest("GET", "/health/live", nil))
	if r.Code != 200 {
		t.Fatal(r.Code)
	}
	r = httptest.NewRecorder()
	h.ServeHTTP(r, httptest.NewRequest("POST", "/api/v1/devices", strings.NewReader(`{}`)))
	if r.Code != 403 {
		t.Fatal(r.Code)
	}
}
func TestInventoryIntegration(t *testing.T) {
	if os.Getenv("TEST_DB") != "1" {
		t.Skip("requires isolated MySQL test database; see README")
	}
	c := mysql.NewConfig()
	c.User = os.Getenv("DB_USER")
	c.Passwd = os.Getenv("DB_PASSWORD")
	c.Net = "tcp"
	c.Addr = os.Getenv("DB_ADDRESS")
	c.DBName = os.Getenv("DB_NAME")
	c.ParseTime = true
	if !strings.HasSuffix(c.DBName, "_test") {
		t.Fatal("refusing non-test database")
	}
	db, e := sql.Open("mysql", c.FormatDSN())
	if e != nil {
		t.Fatal(e)
	}
	defer db.Close()
	if e = Bootstrap(context.Background(), db, "integration@rac.test", "integration-test-password-2026"); e != nil {
		t.Fatal(e)
	}
	h := New(db, false, "http://localhost:4200").Handler()
	var cookie *http.Cookie
	request := func(method, path, body string) *httptest.ResponseRecorder {
		t.Helper()
		r := httptest.NewRequest(method, path, strings.NewReader(body))
		r.Header.Set("Origin", "http://localhost:4200")
		r.Header.Set("Content-Type", "application/json")
		if cookie != nil {
			r.AddCookie(cookie)
		}
		w := httptest.NewRecorder()
		h.ServeHTTP(w, r)
		return w
	}
	if w := request("GET", "/api/v1/devices", ""); w.Code != 401 {
		t.Fatalf("unauthenticated: %d", w.Code)
	}
	if w := request("POST", "/api/v1/auth/login", `{"email":"integration@rac.test","password":"wrong"}`); w.Code != 401 {
		t.Fatal("invalid password accepted")
	}
	w := request("POST", "/api/v1/auth/login", `{"email":"integration@rac.test","password":"integration-test-password-2026"}`)
	if w.Code != 200 {
		t.Fatalf("login %d %s", w.Code, w.Body.String())
	}
	cookie = w.Result().Cookies()[0]
	if !cookie.HttpOnly || cookie.SameSite != http.SameSiteStrictMode {
		t.Fatal("unsafe cookie")
	}
	create := func(path, body string) int64 {
		t.Helper()
		w := request("POST", path, body)
		if w.Code != 201 {
			t.Fatalf("create %s: %d %s", path, w.Code, w.Body.String())
		}
		var result struct {
			ID int64 `json:"id"`
		}
		json.Unmarshal(w.Body.Bytes(), &result)
		return result.ID
	}
	viewerID := create("/api/v1/users", `{"email":"viewer@rac.test","password":"viewer-integration-password","role_id":6}`)
	if w = request("GET", "/api/v1/users", ""); w.Code != 200 || strings.Contains(w.Body.String(), "password") {
		t.Fatal("unsafe users response")
	}
	patchBody, _ := json.Marshal(map[string]any{"role_id": 5, "active": false})
	if w = request("PATCH", fmt.Sprintf("/api/v1/users/%d", viewerID), string(patchBody)); w.Code != 200 {
		t.Fatalf("user update: %d", w.Code)
	}
	b := create("/api/v1/buildings", `{"name":"Integration building"}`)
	body, _ := json.Marshal(map[string]any{"name": "Integration room", "building_id": b})
	l := create("/api/v1/locations", string(body))
	body, _ = json.Marshal(map[string]any{"name": "Test switch", "asset_id": "TEST-001", "location_id": l, "category": "switch", "management_ip": "192.0.2.10"})
	create("/api/v1/devices", string(body))
	if w = request("POST", "/api/v1/devices", string(body)); w.Code != 409 {
		t.Fatal("duplicate accepted")
	}
	if w = request("POST", "/api/v1/devices", `{"name":"Invalid","asset_id":"x","location_id":1,"category":"switch","management_ip":"invalid"}`); w.Code != 400 {
		t.Fatal("invalid IP accepted")
	}
	if w = request("GET", "/api/v1/devices", ""); w.Code != 200 || !strings.Contains(w.Body.String(), "unknown") {
		t.Fatal("inventory read failed")
	}
	var n int
	if e = db.QueryRow("SELECT COUNT(*) FROM audit_logs WHERE action IN ('devices.create','locations.create','buildings.create')").Scan(&n); e != nil || n != 3 {
		t.Fatalf("audit count %d: %v", n, e)
	}
	// Changing the role is an isolated database fixture operation, not an application endpoint.
	if _, e = db.Exec("UPDATE users SET role_id=6 WHERE email='integration@rac.test'"); e != nil {
		t.Fatal(e)
	}
	if w = request("POST", "/api/v1/buildings", `{"name":"Forbidden"}`); w.Code != 403 {
		t.Fatal("viewer write allowed")
	}
	if w = request("GET", "/api/v1/audit", ""); w.Code != 403 {
		t.Fatal("viewer audit access allowed")
	}
	if w = request("POST", "/api/v1/auth/logout", `{}`); w.Code != 204 {
		t.Fatal("logout failed")
	}
	if w = request("GET", "/api/v1/devices", ""); w.Code != 401 {
		t.Fatal("session still valid")
	}
}
