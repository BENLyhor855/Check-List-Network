# Foundation HTTP API

All responses are JSON except successful logout (204). Error envelope: `{"error":"message"}`. Authentication is an opaque HttpOnly cookie. Every mutation requires Origin equal to APP_ORIGIN and application/json. Request body limit: 64 KiB. The current Laravel development server does not enforce the previous Go server’s 10-second request deadline. Lists return at most 100 items and accept nonnegative offset up to 1,000,000. No CORS access is enabled.

| Method | Path | Access / request |
| --- | --- | --- |
| GET | /health/live | process liveness |
| GET | /health/ready | database ping |
| POST | /api/v1/auth/login | email, password; ten attempts/minute per client IP, using the local cache |
| POST | /api/v1/auth/logout | revoke current session, clear cookie |
| GET | /api/v1/auth/me | current id, email, role |
| GET | /api/v1/checks?date=YYYY-MM-DD | inventory.read; devices and manual check results for the date, with totals |
| POST | /api/v1/checks | inventory.write; device_id, date, result (ok, issue, not_applicable), optional notes; updates the same device/date |
| GET | /api/v1/buildings | inventory.read; paginated items |
| POST | /api/v1/buildings | inventory.write; name |
| GET | /api/v1/locations | inventory.read; paginated items |
| POST | /api/v1/locations | inventory.write; name, building_id |
| GET | /api/v1/devices | inventory.read; inventory joined to location/building |
| POST | /api/v1/devices | inventory.write; name, asset_id, location_id, category; optional manufacturer, model, serial_number, management_ip, notes |
| GET | /api/v1/audit | audit.read; newest records first, paginated |
| GET | /api/v1/users | users.manage; first 100 users, no password hashes |
| POST | /api/v1/users | users.manage; email, password (14–72 bytes), role_id |
| PATCH | /api/v1/users/{id} | users.manage; role_id, active; cannot modify self; revokes target sessions |

Create responses: 201 with id. User patch: 200 with id. Errors: 400 validation, 401 authentication, 403 authorization/origin, 409 duplicate/reference conflict, 415 content type, 429 login throttle, 500 database error, 503 readiness failure. SQL values use placeholders.

Role IDs in migration order: 1 Super Administrator, 2 Network Administrator, 3 Network Engineer, 4 IT Technician, 5 Management/Supervisor, 6 Read-only Viewer. Roles 1–4 can create inventory; all roles can read it; 1–2 can read audit; only 1 manages users. Permission assignment UI and custom roles remain planned.

Not yet provided: device edits/deletion, search/filtering, full inventory fields, user password reset, role permission management, failed-action audit, WebSocket events or the later operational APIs. Do not rely on these as available features.
