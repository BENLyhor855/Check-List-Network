# RAC NOC architecture

Status: foundation under implementation; not production ready. Requirements are preserved in requirements.md.

Use an Angular SPA and a Go modular monolith backed by MySQL 8.4. API namespace is /api/v1. Browser traffic goes through the same-origin reverse proxy. Production requires internal HTTPS terminated by the organization’s reverse proxy; management binds loopback by default.

Modules own their repositories and services. Build inventory and identity first, then operations, monitoring, vendor adapters, reporting and AI. Workers use bounded concurrency, deadlines, retry/backoff and deduplicated alerts. Monitoring observations never complete human checklist tasks. Vendor adapters and AI start read-only. No live infrastructure addresses or observations are seeded.

Identity uses bcrypt passwords and opaque random server-side sessions, hashed at rest, expiring after eight hours. Cookies are HttpOnly, SameSite Strict, Secure by default; local development explicitly disables Secure. Mutations require same-origin requests. Roles map to module/operation permissions in MySQL. Bootstrap credentials arrive through runtime variables, never source. Audit events are inserted transactionally with inventory writes. Application credentials receive no UPDATE/DELETE privilege on audit records; database administrators remain a trust boundary. Production additionally requires external append-only audit export.

Relational records use UTC timestamps; the UI defaults to Asia/Phnom_Penh. No invented VLANs or IP assignments. Schema migrations are versioned SQL, tracked in schema_migrations, applied once under a database advisory lock. MySQL DDL is not transactional: backup before upgrades; failed migration requires inspection and restore, never automatic destructive rollback.

Future metrics use time-partitioned bounded retention tables (raw 30 days, hourly 13 months), not an unbounded generic table. Integration secrets require envelope encryption with an external key and rotation before adapters are enabled. Uploads require content limits, MIME validation, malware scanning and authorization before attachment support ships. WebSocket updates must authenticate and authorize subscriptions; not yet implemented.

## Delivery gates
1. Foundation: identity, RBAC, buildings, locations, inventory, migration runner, Angular shell; API and MySQL tests plus frontend build.
2. Operations: audited checklist transitions, supervisor verification, incidents with enforced state transitions, maintenance, approved changes, knowledge articles.
3. Monitoring: isolated ICMP/SNMP/HTTP workers, syslog, retention and alert delivery; mocked failures and outage recovery tests.
4. Integrations: verify official endpoints and version capabilities before implementing PAN-OS, Meraki, UniFi, Proxmox, Synology and Cisco adapters.
5. Reports: scoped queries, CSV/PDF exports, scheduled jobs, restore drills.
6. AI: read-only provider interface, evidence provenance, facts/hypotheses separation, retrieval authorization and evaluation.

Each gate requires implemented behavior and passing tests before claiming completion. Production gates include TLS, credential lifecycle, rate limits across replicas, disaster recovery, security review, accessibility and end-to-end browser tests.

## Implemented code organization

The API composition root is `backend/cmd/api/main.go`. Environment settings live in `internal/config`; database connection and migration lifecycle in `internal/database`; bootstrap authentication in `internal/auth`; shared transactional audit writes in `internal/audit`. Inventory validation and SQL live in `internal/inventory`. The HTTP layer in `internal/api` is split into routing, middleware, authentication, health, JSON responses, inventory and user handlers. Audit writes remain in the same transaction as business mutations. Authentication/user HTTP handlers still own their SQL; these can be extracted when their domain workflows grow.

Angular `app.ts` now composes session restoration, login and the authenticated workspace. `core/auth` owns the session service; `core/api` owns typed request models, inventory access and error messages. `features/auth` contains login; `features/inventory` contains navigation and record creation. `shared/record-table` renders paginated records. Component styles own layout; global styles provide common typography and controls. Requests are cancelled on component destruction and when changing the active inventory section. Form instances are destroyed on cancel/navigation, clearing their state.

The HTTP paths, database schema, deployment commands and dependency versions remain unchanged. Offset parsing now rejects partially numeric values such as `1junk`. The refactor is about maintainability, not a measured performance improvement.
