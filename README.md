# RAC Network Operations

Angular + Go + MySQL foundation for the Royal Academy of Cambodia. This is an initial working increment, not the completed production platform. Requirements are preserved in [docs/requirements.md](docs/requirements.md); design and delivery gates are in [docs/architecture.md](docs/architecture.md).

Implemented: sign-in/out, database-backed sessions and role checks, building/location/device registration and lists, transactional inventory audit trail, API user creation/role updates, responsive Angular workspace, versioned MySQL migration runner and Compose deployment. Device status remains unknown; no monitoring or vendor data is fabricated.

For step-by-step local setup, login, manual checks and automated tests, see [Run and test locally](docs/local-development.md).

## Development

Requirements: Docker with Compose, Node 24, Go 1.27.3 (the operating system binary named `go` may be an unrelated utility; cloud toolchain is `/workspace/toolchains/go/bin/go`).

1. Copy `.env.example` to `.env`. Set unique DB_PASSWORD, DB_ROOT_PASSWORD, BOOTSTRAP_EMAIL and a random 14–72 byte BOOTSTRAP_PASSWORD. Keep `.env` private (mode 0600). No credentials belong in Git.
2. `docker compose build` (uses a read-only BuildKit CA mount, default system trust bundle; set RAC_CA_BUNDLE if needed).
3. `docker compose up -d`. MySQL starts, migration and bootstrap jobs finish, then API and web start. Database storage is a named volume.
4. Check `docker compose ps -a` and make a local request to port 4200 `/health/ready`; expect `{"status":"ready"}`. Sign in using the private bootstrap values in `.env`. Register actual buildings and locations before devices.

The web port binds loopback only. Local HTTP sets COOKIE_SECURE=false explicitly. Production requires internal HTTPS, APP_ORIGIN matching the HTTPS origin, COOKIE_SECURE=true and a trusted reverse proxy. Do not publish this development configuration to the Internet.

### Native iteration

Start only the database: `docker compose up -d db`. Export the DB values securely from your local `.env`, set DB_ADDRESS=127.0.0.1:3306, DB_USER=racnoc, DB_NAME=racnoc. From backend, run `go run ./cmd/api migrate`, then `go run ./cmd/api bootstrap` once, and `go run ./cmd/api`. Export APP_ORIGIN=http://localhost:4200 and COOKIE_SECURE=false for local HTTP. From frontend: `npm ci`, then `npm start -- --host 127.0.0.1 --proxy-config proxy.conf.json`. Do not create a Git worktree: cloud tasks already have isolated checkouts.

## Validation

From backend: `go test ./...` runs the unit test and explicitly skips the database suite without TEST_DB=1. Full database integration requires a fresh isolated database whose name ends `_test`, migrated with the same runner. Set DB_NAME to that database and TEST_DB=1, then `go test -v ./...`. Never use the persistent development database for this test. Tests exercise login, invalid credentials, sessions, inventory, duplicate and IP validation, audit, user administration, viewer denial and logout. The test database must be fresh on every run.

From frontend: `npm test -- --watch=false` and `npm run build`.

From the repository root, run `python3 scripts/test-integration.py` to create a unique disposable database, apply migrations twice, run the full Go suite and remove only that database. It uses the running Compose database and the ignored local `.env`; it does not change development inventory. Set `GO_BINARY` if the Go compiler is not on PATH. In this cloud environment use `GO_BINARY=/workspace/toolchains/go/bin/go GOCACHE=/workspace/.cache/go-build python3 scripts/test-integration.py`.

With Compose running, `node scripts/smoke.mjs` checks the HTTP workflow. `node scripts/test-browser.cjs` checks the actual UI in Chromium: invalid and valid login, session persistence across reload, navigation, required fields, audit display, mobile layout, logout and browser credential protection. The browser test requires the Playwright Node package and Chromium, both supplied by this cloud environment; set CHROMIUM_PATH for another browser installation. It logs in with local bootstrap credentials and adds normal login audit records, but creates no inventory records. It does not yet cover inventory creation through the browser, live devices or later-phase modules.

## Backup and restore

Stop application writes first. Use `docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction --routines --triggers racnoc'` redirected to a protected file outside Git. Encrypt backups, retain off-host copies and test restoration in an isolated database. Restore with the matching mysql client through stdin and a separately created database. Never overwrite the live database without a reviewed recovery plan. Named volumes persist across `docker compose down`; `down -v` destroys them. See [database policy](docs/database.md) for migration and rollback rules.

## Limitations and next work

Foundation is partial: full inventory metadata, edits, permission administration UI, password lifecycle, failed-action audit and production database privilege separation remain. Compose uses one database user for migration/runtime in development; split credentials and enforce audit INSERT/SELECT-only privileges before production. Login throttling is per process and socket peer; put a trusted edge limiter in front of multiple replicas. Session cleanup, secure uploads, encrypted vendor secrets, TLS provisioning, monitoring, operations workflows, reporting and AI follow the roadmap. No vendor API calls or network configuration writes are implemented.

API details: [docs/api.md](docs/api.md). Schema design: [docs/database.md](docs/database.md).

## Network deployment checklist

The original vendor-neutral deployment checklist and site-record template remain available. See [network checklist guidance](docs/network-checklist.md), [deployment checklist](checklists/network-deployment.md), [site record template](templates/site-record.md), and [checklist task breakdown](docs/task-breakdown.md). Keep completed site records and sensitive infrastructure data private.

## Code organization

- `backend/cmd/api`: application entry point.
- `backend/internal/config`, `database`: configuration, connections and migrations.
- `backend/internal/api`: HTTP routing, middleware and handlers.
- `backend/internal/auth`, `audit`, `inventory`: bootstrap, audit writes, inventory validation and persistence.
- `frontend/src/app/core`: typed API clients and session management.
- `frontend/src/app/features`: login, inventory workspace and record form.
- `frontend/src/app/shared`: reusable record table.

See [architecture](docs/architecture.md) for module responsibilities.
