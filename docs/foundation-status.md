> Historical record: these results describe a previous implementation and do not verify the current Laravel application. See the root README for current setup.

# Initial foundation increment

Implemented: Go API and Angular workspace, MySQL migrations, opaque sessions, bcrypt passwords, fixed seeded roles and database permission checks, building/location/device creation and lists, transactional inventory audit, API user creation and role/deactivation updates. Architecture, future schema plan, API contract, backup/rollback guidance and original requirements are documented.

Files: backend/cmd/api, backend/internal/api, backend/migrations, frontend/src/app, frontend proxy and Nginx configuration, Dockerfiles, compose.yaml, scripts/smoke.mjs, README.md and docs. Dependency versions are locked in go.sum and package-lock.json. Credentials are only in ignored local .env.

Database: migration 001 adds roles, permissions, role_permissions, users, sessions, buildings, locations, devices and audit_logs; runner tracks schema_migrations. Migration application and repeat application passed against MySQL 8.4.13. Development inventory remains empty.

Verification executed:

- Backend unit test and isolated MySQL integration test: both passed. Integration covers authentication, invalid password, cookie attributes, user creation/update, building/location/device registration, duplicate and IP validation, audit, viewer authorization, logout and session revocation.
- Angular component test: 1 passed; unauthenticated session shows sign-in.
- Angular production build: passed; component stylesheet exceeds the 4 kB warning budget by 225 bytes (below the error budget).
- All Compose images built; migration and bootstrap jobs exited 0; database healthy; API and web running.
- HTTP smoke through Nginx: web page, readiness, login, inventory access, logout and revoked session passed, including after API/web restart.

Run and verify: follow README.md, then run `node scripts/smoke.mjs` from the checkout. Local bootstrap credentials are in private .env; do not print or commit them. Tests do not require actual RAC devices.

Limitations: Phase 1 is not fully complete. Inventory editing and full metadata, configurable role administration UI, password lifecycle, failed-action audit, comprehensive frontend/form/browser tests and production database privilege separation remain. The runtime development user currently has broad database privileges. Production security review, TLS deployment, session cleanup and replicated rate limiting remain. Later operations, monitoring, integrations, reporting and AI phases are not implemented. No claims of production readiness or live network visibility are made.

Follow-up testing on 6 November 2026: database, frontend, Compose and Chromium smoke checks passed. Reusable runners and detailed scope are recorded in [test-results.md](test-results.md). Browser smoke coverage now exists; full inventory creation through the browser and comprehensive frontend coverage remain pending.

Next increment: finish foundation gaps and complete browser workflow coverage before beginning audited checklist and incident workflows. Use the roadmap in architecture.md.

Environment configuration: install_script and start_skill saved in the draft; publication and restoration in a new task have not been verified. User must review/save the environment settings and publish to activate a snapshot. The application foundation is being added alongside the existing network checklist documentation.
