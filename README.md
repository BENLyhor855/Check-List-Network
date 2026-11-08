# RAC Network Operations

A Laravel 12 and MySQL foundation for the Royal Academy of Cambodia. Laravel serves the workspace and API for sign-in, role-based access, building, location and device registration, and audit records. Device status remains unknown until monitoring is connected.

## Run locally

Install PHP 8.4 or later, Composer, MySQL 8.4, and the PHP extensions required by Laravel and Composer, including PDO MySQL, PDO SQLite (for tests), mbstring, XML, ctype, fileinfo, OpenSSL and tokenizer. Make sure the MySQL service is running.

1. Create a MySQL database and a local database user with permission to create and alter its tables. For example, create an empty database named `racnoc`; configure its user and password in the next step. Use a separate database for testing.
2. From a terminal in the project root, run:

   ```sh
   cd laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Edit `laravel/.env`: set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to your local MySQL credentials. Set `BOOTSTRAP_EMAIL` and a unique `BOOTSTRAP_PASSWORD` of 14–72 bytes for the first administrator. Keep this file private. `APP_URL` defaults to `http://localhost:4200` and must match the browser origin, including port.
4. Run:

   ```sh
   php artisan rac:prepare
   php artisan serve --host=127.0.0.1 --port=4200
   ```

   Open `http://localhost:4200` and sign in with the bootstrap credentials. The preparation command applies the foundation SQL once and creates the first administrator only if the user table is empty. It recognizes the previous migration marker and preserves existing records. Back up an existing database and test with a restored copy before switching traffic.

## Tests

From `laravel/`, run `php artisan test`; tests use an isolated in-memory SQLite database. With the local server and MySQL running, `node scripts/smoke.mjs` from the project root checks readiness, login, inventory access and logout. This smoke check signs in with the bootstrap credentials and creates a login audit entry.

See the [API contract](docs/api.md), [requirements](docs/requirements.md), [database notes](docs/database.md), and [network deployment checklist](checklists/network-deployment.md). Keep credentials, site records and infrastructure data private.

## Limitations

The built-in server and file-based throttling are for local development. Production needs an appropriate PHP application server, HTTPS, shared rate limiting, hardened database privileges, a security review and tested backups. Password lifecycle, device editing, monitoring and later operational modules remain future work.
