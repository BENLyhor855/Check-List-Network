# RAC NOC architecture

The foundation uses a Laravel 12 application for its server-rendered workspace and JSON API, backed by MySQL 8.4. See [requirements](requirements.md) for the wider roadmap. The browser makes same-origin requests to Laravel; the API uses opaque HttpOnly session cookies, bcrypt passwords, role permissions and transactional audit records. Device status is unknown without monitoring.

The Laravel `rac:prepare` command uses the existing `schema_migrations` marker and the same foundation SQL as the earlier implementation. It holds the existing MySQL advisory lock and creates the first administrator only when the users table is empty. MySQL DDL is not transactional; back up before upgrades and inspect partial failures before retrying. It does not seed fictional infrastructure.

Production requires an appropriate PHP server, HTTPS, shared rate limiting, credential lifecycle, a security review, privilege separation, audit export and recovery exercises. Monitoring, integrations, reports and AI remain future work.
