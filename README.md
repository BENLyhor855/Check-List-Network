# RAC Network Operations

A Laravel 12 and MySQL foundation for the Royal Academy of Cambodia. Laravel serves both the network workspace and its API. The application provides sign-in, role-based access, building, location and device registration, and an audit trail. Device status remains unknown until monitoring is connected.

## Local setup

Requirements: Docker and Compose. This development configuration binds web and MySQL to loopback.

1. Copy `.env.example` to `.env` and protect it (`chmod 600 .env`). Set unique `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `BOOTSTRAP_EMAIL`, and a 14–72 byte `BOOTSTRAP_PASSWORD`. Generate `APP_KEY` as `base64:` followed by 32 random bytes in base64, for example `printf 'base64:'; openssl rand -base64 32`.
2. Run `docker compose build` and `docker compose up -d`. The Laravel `rac:prepare` command applies the existing foundation SQL once under a database lock and creates the bootstrap administrator only if no users exist. It recognizes the earlier `schema_migrations` marker and preserves existing records. Back up an existing database and test on a restored copy before switching traffic.
3. Open `http://localhost:4200` and sign in with the bootstrap account. `/health/ready` checks the database connection. `node scripts/smoke.mjs` tests login and logout against the running stack.

Builds use an optional BuildKit CA secret (`RAC_CA_BUNDLE`, default `/etc/ssl/certs/ca-certificates.crt`); for standard local Docker, use `docker compose -f compose.yaml -f compose.local.yaml build`.

## Tests

From `laravel/`, run `composer install` and `php artisan test` with PHP 8.4+ and SQLite PDO. Tests use an isolated in-memory database. For the MySQL startup workflow, run the local stack above against a disposable database before using an existing installation.

The [API contract](docs/api.md), [requirements](docs/requirements.md), [database notes](docs/database.md), and [network deployment checklist](checklists/network-deployment.md) remain available. Keep credentials, completed site records and infrastructure data private.

## Limitations

This foundation is not production ready. Compose uses Laravel's development server and local file-backed throttling. Production needs an appropriate PHP application server, HTTPS, shared rate limiting, hardened database privileges, a security review and tested backups. Password lifecycle, device editing, monitoring and later operational modules remain future work.
