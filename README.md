# RAC Network Operations

An Angular, Laravel and MySQL foundation for the Royal Academy of Cambodia. It provides sign-in, role-based access, building/location/device registration, and an inventory audit trail. Device status remains unknown until a monitoring integration exists. Requirements remain in [docs/requirements.md](docs/requirements.md).

The HTTP API runs on Laravel 12; Angular remains the frontend. To preserve existing installations, this transition retains the existing MySQL schema and the Go migration and first-user bootstrap jobs in `backend/`. Existing bcrypt passwords and unexpired `rac_session` cookies remain compatible. The Go HTTP server is retained for reference and rollback but is no longer the Compose API service.

## Local setup

Requirements: Docker and Compose. This configuration binds the web and MySQL ports to loopback and is for local development only.

1. Copy `.env.example` to `.env` and protect it (`chmod 600 .env`). Set unique `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `BOOTSTRAP_EMAIL`, and a 14–72 byte `BOOTSTRAP_PASSWORD`. Generate `APP_KEY` as `base64:` followed by 32 cryptographically random bytes encoded in base64 (for example, `printf 'base64:'; openssl rand -base64 32`). Keep all values private.
2. Run `docker compose build`, then `docker compose up -d`. On first startup the existing migration and bootstrap jobs initialize MySQL before Laravel starts. They do not replace existing users or inventory.
3. Open `http://localhost:4200` and sign in with the bootstrap account. `/health/ready` checks the database connection. For an existing database, back it up first and test the transition on a restored copy before switching traffic.

Builds use an optional BuildKit CA secret (`RAC_CA_BUNDLE`, default `/etc/ssl/certs/ca-certificates.crt`); on a standard local Docker installation use `docker compose -f compose.yaml -f compose.local.yaml build`. Local HTTP uses `COOKIE_SECURE=false`; production requires HTTPS, matching `APP_ORIGIN`, `COOKIE_SECURE=true`, proper access controls, a production PHP server and the security work below.

## Tests

- Laravel: from `laravel/`, run `composer install` and `php artisan test` with PHP 8.4 or newer and the SQLite PDO extension. Tests use an isolated in-memory database.
- Angular: from `frontend/`, run `npm ci`, `npm test -- --watch=false`, and `npm run build`.
- With the Compose stack running and private `.env` configured, `node scripts/smoke.mjs` checks the web, readiness, login, inventory access, and logout through the reverse proxy. `node scripts/test-browser.cjs` additionally requires Playwright and Chromium.
- The original Go migration/bootstrap suite remains available as documented in [local development](docs/local-development.md); it tests legacy code, not the Laravel API.

The HTTP contract is described in [docs/api.md](docs/api.md), and the schema and backup guidance in [docs/database.md](docs/database.md). Some older documents describe the previous Go HTTP implementation; [architecture](docs/architecture.md) identifies the current transition state.

## Limitations

This is not production ready. Compose currently uses Laravel's development server and local file-backed login throttling; it is not suitable for multiple replicas. Production needs a proper PHP application server, shared rate limiting, TLS, a security review, hardened database privileges, backup/restore exercises, and session cleanup. Password lifecycle, device editing, monitoring and other later-phase modules remain future work. Keep completed site records, credentials, and infrastructure data private.

The original [network deployment checklist](checklists/network-deployment.md), [site record](templates/site-record.md), and [task breakdown](docs/task-breakdown.md) remain available.
