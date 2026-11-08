# Database plan and migration policy

Implemented: roles, permissions, role_permissions, users, sessions, buildings, locations, devices, audit_logs and schema_migrations. See laravel/database/schema/001_foundation.sql for foreign keys, unique constraints, lookup indexes and timestamps. Inventory starts empty and device status is unknown; no four-building names or addressing is assumed.

Planned migrations, in dependency order:

| Module | Tables and relationships |
| --- | --- |
| Inventory expansion | vendors; device_interfaces → devices; network_topologies; network_links → interfaces; warranty, MAC, firmware and ownership fields on devices |
| Operations | checklist_templates → template_items; checklist_schedules → templates; checklist_runs → schedules; checklist_run_items → runs and immutable template snapshots; task_evidence and task_audit → run_items; maintenance_schedules → devices; maintenance_records → schedules |
| Incidents | incidents → devices/buildings/users; incident_updates → incidents; problem_records and problem_incidents; state transition audit, response/resolution SLA timestamps |
| Changes | change_requests → requester; change_devices; change_approvals → approver; configuration_backups → devices and changes; backup object checksum, encrypted storage path and retention |
| Network | wan_connections → interfaces; vlans; subnets → vlans/buildings; ip_addresses → subnets; unique address per routing domain; overlap validation in service transaction |
| Integrations | integrations → vendors; integration_credentials → integration with encrypted ciphertext, key version and rotation metadata; monitoring_checks → devices/integrations; monitoring_jobs; monitoring_alerts with deduplication key and state |
| History | time-partitioned monitoring_metrics and hourly aggregates; indexes on check_id/time; drop expired partitions; keep referenced incident evidence separately |
| Knowledge | knowledge_articles, article_versions, article_attachments and incident_article links; authorization and sanitized rendering |
| Delivery | notifications → users; notification_attempts; report_jobs with requester, scope, schedule, output expiration; delivery retries and idempotency keys |

All business records require created_at/updated_at and bounded text fields. History and audit are append-only to the runtime database principal. Each write that changes workflow state creates audit within the same transaction. Enumerated state transitions belong in tested service logic; database constraints reinforce valid states. Delete behavior is restrictive by default. Historical records use immutable snapshots where edits would change past evidence.

Apply: `cd laravel && php artisan rac:prepare`. The migration runner takes an advisory lock and records successful filenames. Never edit an applied migration. Add a forward migration. Current simple runner supports statement-separated SQL only, not procedures or delimiters.

Rollback: this release deliberately has no automatic destructive down migration. Stop writes, create an encrypted backup, test its restoration to a separate database, then restore the preceding database and matching application version. Removing a database destroys its data and requires explicit operator action.
