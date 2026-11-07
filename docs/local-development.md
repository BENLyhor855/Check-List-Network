# Run and test locally

This guide runs the implemented foundation: login, buildings, locations, device registration and audit records. Monitoring and later operational modules are not implemented yet.

## Prerequisites

- Git.
- Docker Engine or Docker Desktop with Compose 2.24.4 or newer. Start Docker Desktop before running commands. Use Linux containers.
- Optional for automated tests outside Docker: Node.js 24, Go 1.27.3 and Python 3.

Run `docker compose version` to check Compose. Commands below work in a terminal from the repository root unless otherwise stated.

## 1. Get the code

```sh
git clone https://github.com/BENLyhor855/Check-List-Network.git
cd Check-List-Network
```

For an existing clone, use `git pull --ff-only` once the desired commit has been pushed to GitHub. Local uncommitted changes may need to be committed or stashed first.

## 2. Configure your local account

Linux, macOS or Git Bash:

```sh
cp .env.example .env
chmod 600 .env
```

PowerShell:

```powershell
Copy-Item .env.example .env
```

Open `.env` in your editor. Fill the blank values; do not leave the examples empty:

| Variable | What to enter |
| --- | --- |
| `DB_PASSWORD` | A unique random password for the development database user |
| `DB_ROOT_PASSWORD` | A different random database administrator password |
| `BOOTSTRAP_EMAIL` | The email address you want to use to sign in |
| `BOOTSTRAP_PASSWORD` | A unique random password, 14–72 bytes |
| `APP_ORIGIN` | Keep `http://localhost:4200` for the default setup |
| `COOKIE_SECURE` | Keep `false` for local HTTP only |

Use password-manager-generated alphanumeric values to avoid shell/Compose interpolation issues. Write values without surrounding quotes so the existing test scripts can read them consistently. Do not commit `.env`.

The bootstrap command creates the first administrator only when the users table is empty. Editing BOOTSTRAP_PASSWORD afterward does not reset an existing account. Database passwords also initialize only an empty MySQL volume; changing `.env` does not rotate existing database credentials.

## 3. Build and start

```sh
docker compose -f compose.yaml -f compose.local.yaml up -d --build
docker compose -f compose.yaml -f compose.local.yaml ps -a
```

The local override uses normal container CA trust and avoids depending on a Linux CA file on your Windows/macOS host. The managed cloud environment continues to use `compose.yaml` alone with its proxy CA. On a corporate TLS-inspecting network, use the base Compose file with `RAC_CA_BUNDLE` pointing to an approved PEM CA bundle instead; do not disable TLS verification.

Expected status:

- `db`: running and healthy.
- `migrate` and `bootstrap`: exited with code 0; these are one-time startup jobs.
- `api` and `web`: running.

On **your local computer**, open `http://localhost:4200` and sign in with your BOOTSTRAP_EMAIL and BOOTSTRAP_PASSWORD. Use `localhost` consistently: switching to `127.0.0.1` changes the origin and mutation requests will be rejected.

## 4. Manual checks

1. Sign in; the inventory page should open without fabricated devices.
2. Add a building.
3. Add a location belonging to that building.
4. Add a device with a unique asset ID and that location. Its status should be `unknown` until monitoring is implemented.
5. Open Audit trail and confirm the record-creation events.
6. Sign out, reload, and confirm the sign-in screen appears.

These manual checks create persistent local records. The current foundation does not yet provide inventory editing/deletion.

## 5. Automated checks

With Node 24 installed and Compose running:

```sh
node scripts/smoke.mjs
```

Expected: `PASS` for readiness, login, inventory access, logout and session revocation. Credentials are read from `.env` without printing them.

Frontend (requires Node 24):

```sh
cd frontend
npm ci
npm test -- --watch=false
npm run build
cd ..
```

Backend unit/static checks (requires Go):

```sh
cd backend
go test -count=1 -v ./...
go vet ./...
cd ..
```

The normal Go test command skips the MySQL integration test. Run the full suite from the repository root with the database running:

```sh
python3 scripts/test-integration.py
```

On Windows, use `py -3 scripts/test-integration.py` if `python3` is unavailable. The runner creates a uniquely named test database, applies migrations twice, runs the Go tests, then removes that database. It does not remove development inventory. The Go compiler must be on PATH, or set GO_BINARY to its executable path. Port 3306 must match the default Compose mapping.

The optional `scripts/test-browser.cjs` requires Playwright to be resolvable by Node and a Chromium executable (CHROMIUM_PATH defaults to `/usr/bin/chromium`). These are provided in the cloud environment but are not installed by `npm ci` in this repository. The HTTP smoke and Angular tests above do not require Playwright.

## Stop, restart and update

```sh
docker compose -f compose.yaml -f compose.local.yaml stop
docker compose -f compose.yaml -f compose.local.yaml up -d
```

After receiving code updates, rerun the build/start command from step 3. Database data is stored in a named volume. Do not use `down -v` unless you intentionally want to erase it. Backup and migration rollback guidance is in README.md and docs/database.md.

## Troubleshooting

- **Port already allocated:** stop the conflicting local service on 4200 or 3306. Changing the web port also requires APP_ORIGIN to match.
- **Database/migration startup fails:** inspect `docker compose logs --tail=100 db migrate bootstrap` locally. Check whether `.env` matches the credentials that initialized the volume. Avoid posting sensitive logs publicly.
- **Login fails:** use the credentials from the original bootstrap. Editing `.env` does not update an existing user.
- **403 same-origin request required:** open the exact APP_ORIGIN in your browser; rebuild/recreate the API after changing its environment.
- **Build reports a missing CA file:** include `-f compose.local.yaml` in the local build command as shown above.
- **Go command behaves unexpectedly:** verify `go version`; some systems have an unrelated utility named `go`.
