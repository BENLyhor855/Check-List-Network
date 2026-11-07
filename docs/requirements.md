# Project: RAC Network Operations Management & Intelligent NOC Platform

Act as a Senior Software Architect, Senior Go Backend Engineer, Senior Angular Developer, Network Engineer, DevOps Engineer, and UI/UX Designer.

Your task is to design and build a production-ready Network Operations Management Platform for Royal Academy of Cambodia (RAC).

The platform must help the internal IT and Network Engineering team manage network infrastructure, daily operations, preventive maintenance, incidents, network monitoring, configuration changes, documentation, and management reports.

Do not build only a basic CRUD application. Build a complete, modular Network Operations Management System that can evolve into an intelligent AI-powered NOC platform.

---

## 1. Existing Infrastructure

Design the system around this environment:

**Organization**
- Royal Academy of Cambodia.
- Four buildings.
- Internal IT and Network Engineering team.

**Network Infrastructure**
- Firewall: Palo Alto PA-410.
- WAN providers: MPTC and ISP-ONLINE.
- Routing: Dual WAN, ECMP and failover.
- Switching: Cisco Catalyst switches.
- Wireless: Cisco Meraki MR44.
- Switching and wireless: UniFi switches and APs.
- UniFi management: Cloud Key Gen2 Plus.
- NAS: Synology DS3617xs.
- Virtualization: Proxmox VE.
- Servers: Ubuntu Linux VMs.

**Network Services**
- VLAN and subnet management.
- DHCP and DNS.
- Internet connectivity.
- VPN.
- NAT and security policies.
- Internal and external applications.
- Nginx and reverse proxies.
- Cloudflare services.

The platform must support additional buildings, devices, vendors, and network technologies without requiring major architectural changes.

---

## 2. Technology Stack

Use the following technologies:

**Frontend**
- Angular.
- TypeScript.
- Angular Material or a suitable modern component library.
- Responsive layouts for desktop, tablet and mobile.
- Real-time dashboards.

**Backend**
- Go (Golang).
- REST API.
- WebSocket for real-time notifications and updates.
- Modular service architecture.
- Background workers for monitoring and scheduled jobs.

**Database**
- MySQL.
- Database migrations.
- Proper indexing and relational database design.
- Historical data management.

**Infrastructure**
- Docker and Docker Compose.
- Ubuntu Server deployment.
- Existing Proxmox environment.
- Open-source technologies wherever practical.

Use Asia/Phnom_Penh as the default timezone.

---

## 3. Core Modules and Functional Requirements

### Module 1: NOC Dashboard

Build a centralized dashboard displaying:

- Overall network health.
- Online and offline devices.
- WAN status.
- MPTC and ISP-ONLINE connectivity.
- Latency, packet loss and bandwidth.
- Firewall health.
- Switch health.
- Wireless AP health.
- Active incidents.
- Pending work checklist.
- Upcoming maintenance.
- Recent configuration changes.
- Critical alerts.

Provide summary widgets, charts, status indicators, and historical performance graphs.

Allow users to filter information by building, device category, location, and time range.

### Module 2: Network Asset and Inventory Management

Manage all network assets.

Required information:

- Device name.
- Asset ID.
- Manufacturer.
- Model.
- Serial number.
- Management IP.
- MAC address.
- Device category.
- Building and location.
- Management interface.
- Firmware version.
- Purchase information.
- Warranty expiration.
- Installation date.
- Responsible engineer.
- Operational status.
- Notes and documentation.

Support relationships between devices, interfaces, uplinks, and network locations.

### Module 3: Network Topology

Create an interactive network topology visualization.

Show:

- Internet providers.
- WAN connections.
- Firewalls.
- Core switches.
- Access switches.
- Wireless access points.
- Servers.
- NAS devices.
- Building connections.
- Device dependencies.

Show device status and connection status.

Allow engineers to manually create and edit topology relationships.

Design the architecture to support future automated topology discovery using SNMP and vendor APIs.

### Module 4: Daily Network Work Checklist

This is one of the most important modules.

Allow administrators to create reusable checklist templates.

Checklist frequencies:
- Daily.
- Weekly.
- Monthly.
- Quarterly.
- Custom schedules.

Example daily checklist:

WAN:
- Verify MPTC connection.
- Verify ISP-ONLINE connection.
- Check latency and packet loss.
- Review ECMP and failover status.

Firewall:
- Check Palo Alto PA-410 health.
- Review CPU and memory.
- Review active sessions.
- Check system and security logs.
- Review WAN routing.

Switching:
- Check Cisco switch status.
- Verify uplinks.
- Check interface errors.
- Check port utilization.

Wireless:
- Check Meraki MR44 status.
- Check UniFi AP status.
- Review connected clients.
- Check AP utilization and connectivity.

Servers:
- Check Proxmox health.
- Check Ubuntu VM status.
- Check Synology storage capacity.
- Verify important services.

Services:
- Verify DHCP and DNS.
- Verify internal applications.
- Verify public website availability.
- Check Cloudflare connectivity.

Checklist functionality:
- Assign tasks to engineers.
- Set deadlines.
- Mark tasks completed.
- Record actual completion time.
- Add comments.
- Attach screenshots and evidence.
- Record issues discovered.
- Create incidents directly from failed checklist items.
- Supervisor verification.
- Completion percentage.
- Overdue task notifications.
- Daily operational summary.

Every completed task must have an audit record.

Never automatically mark a task as completed simply because a monitoring check passed. Monitoring results can provide supporting evidence, but the checklist completion must follow the configured workflow.

### Module 5: Preventive Maintenance Management

Provide a maintenance management system.

Features:
- Maintenance schedules.
- Maintenance categories.
- Recurring tasks.
- Device-specific maintenance.
- Maintenance assignment.
- Maintenance instructions.
- Maintenance evidence.
- Maintenance history.
- Next maintenance date.
- Overdue notifications.
- Maintenance reports.

Allow administrators to define maintenance procedures for firewalls, switches, APs, servers and NAS devices.

### Module 6: Incident and Problem Management

Build a professional incident management module.

Incident information:
- Incident ID.
- Title.
- Description.
- Affected building.
- Affected device.
- Incident category.
- Severity.
- Priority.
- Assigned engineer.
- Detection time.
- Response time.
- Resolution time.
- Root cause.
- Action taken.
- Resolution details.
- Supporting files.
- Related incidents.

Severity levels:
- Critical.
- High.
- Medium.
- Low.

Incident workflow:

New → Acknowledged → Investigating → Identified → Resolving → Resolved → Closed.

Additional features:
- Incident timeline.
- SLA tracking.
- Escalation rules.
- Root cause analysis.
- Related incident detection.
- Incident statistics.
- Recurring problem identification.
- Incident-to-maintenance conversion.

Provide troubleshooting templates for common network problems, including:
- Internet connectivity failures.
- ECMP instability.
- WAN failover problems.
- VLAN connectivity problems.
- DHCP and DNS failures.
- Wireless connectivity problems.
- Firewall issues.
- Cloudflare 522 and 502 errors.
- Server outages.

### Module 7: Network Monitoring

Build a monitoring engine with a modular integration architecture.

Supported monitoring methods:
- ICMP ping.
- SNMP v2c/v3, with v3 preferred.
- HTTP/HTTPS health checks.
- Syslog ingestion.
- REST API integrations.
- Linux metrics exporters.
- Vendor-specific integrations.

Monitor:
- Device availability.
- CPU utilization.
- Memory utilization.
- Interface traffic.
- Interface errors.
- Network latency.
- Packet loss.
- Bandwidth utilization.
- Device uptime.
- Temperature, where supported.
- Wireless client count.
- Storage utilization.
- Service availability.

Support:
- Configurable polling intervals.
- Threshold configuration.
- Historical metrics.
- Alert rules.
- Alert deduplication.
- Retry and backoff.
- Monitoring job status.
- Device monitoring health.

Provide monitoring data through real-time dashboards.

### Module 8: WAN Management

Create a dedicated dual-WAN management dashboard.

Support:
- MPTC.
- ISP-ONLINE.

Display:
- Current status.
- Public IP.
- Interface status.
- Latency.
- Packet loss.
- Bandwidth utilization.
- Uptime.
- Availability history.
- Outage history.
- Failover events.
- Recovery events.

Provide historical comparison between both WAN connections.

Include a dedicated ECMP monitoring page showing available route and path health information obtained through supported interfaces.

The system must initially be read-only for firewall routing and configuration. Do not automatically modify ECMP, static routes, NAT or firewall policies.

### Module 9: Firewall Management

Provide a dedicated Palo Alto PA-410 integration.

Features:
- Device health.
- Interface status.
- WAN connection status.
- Routing information.
- Virtual router information.
- System resource information.
- Session statistics.
- Security log summaries.
- Configuration backup history.
- Configuration version records.
- Configuration change records.

Use officially supported PAN-OS APIs and verify the supported API capabilities before implementing integration endpoints.

Initially use read-only API access.

Any future write operations must require an approved change request, explicit human confirmation, appropriate authorization, pre-change backup and rollback documentation.

### Module 10: Wireless Analytics

Integrate Meraki and UniFi.

Meraki:
- Organization information.
- Network information.
- MR44 access point status.
- Connected wireless clients.
- Client usage.
- AP health.
- Wireless availability.
- Relevant API-supported statistics.

UniFi:
- Controller information.
- Site information.
- Access point status.
- Switch status.
- Connected clients.
- Device health.
- Client activity.
- Network usage.

Allow the administrator to configure API credentials through a secure settings interface.

Do not hardcode API keys.

Design each vendor as an independent integration adapter.

### Module 11: VLAN and IP Address Management

Manage:
- VLAN ID.
- VLAN name.
- Subnet.
- Gateway.
- DHCP range.
- DNS servers.
- Building.
- Associated switch.
- Purpose.
- Responsible department.
- Security zone.
- Status.

Support:
- Subnet documentation.
- IP address allocation.
- Static IP records.
- DHCP documentation.
- Duplicate IP detection where data is available.
- VLAN utilization reports.

Do not assume existing IP addressing or VLAN assignments. Allow administrators to import and configure actual infrastructure information.

### Module 12: Configuration Backup and Change Management

Build a configuration change management system.

Features:
- Change request.
- Change description.
- Affected devices.
- Change category.
- Risk assessment.
- Approval workflow.
- Implementation schedule.
- Pre-change backup.
- Implementation records.
- Post-change verification.
- Rollback plan.
- Post-change backup.
- Change history.

Track configuration versions when officially supported device integrations permit it.

Provide comparison capabilities between configuration versions where technically feasible.

All configuration changes must require appropriate authorization and audit records.

### Module 13: Knowledge Base and Documentation

Create an internal technical knowledge base.

Support:
- Network SOPs.
- Troubleshooting guides.
- Configuration guides.
- Installation procedures.
- Maintenance procedures.
- Network diagrams.
- Vendor documentation.
- Incident resolution notes.

Allow engineers to convert resolved incidents into reusable knowledge articles.

### Module 14: Reports and Analytics

Generate:

Daily reports:
- Network operational status.
- Completed checklist tasks.
- Pending tasks.
- Active incidents.
- Important alerts.
- Work performed.

Weekly reports:
- Infrastructure performance.
- Incident statistics.
- Maintenance completion.
- WAN availability.
- Outstanding tasks.

Monthly reports:
- Network uptime.
- WAN performance comparison.
- Device health.
- Incident trends.
- Preventive maintenance performance.
- Infrastructure risks.
- Recommendations.

Support:
- PDF export.
- Excel/CSV export.
- Date filtering.
- Building filtering.
- Scheduled reports.
- Print-friendly layouts.

### Module 15: Notifications and Alerts

Provide:
- In-application notifications.
- Email notifications.
- Optional Telegram integration.
- Critical alert escalation.
- Maintenance reminders.
- Overdue checklist notifications.
- Incident assignment notifications.
- Change approval notifications.

Support configurable alert rules and notification preferences.

### Module 16: AI-NOC Assistant

Design an optional AI assistant that can help the internal IT team.

Features:
- Summarize active incidents.
- Analyze monitoring alerts.
- Identify related device failures.
- Suggest possible root causes.
- Recommend troubleshooting steps.
- Summarize daily work.
- Generate incident reports.
- Answer questions using the internal knowledge base.
- Identify recurring network problems.

Use a modular AI provider interface to support local or external AI models.

The AI must distinguish confirmed facts from hypotheses.

Initially, AI must be read-only. It must never directly execute firewall commands, modify network configuration, or perform remediation without a separately designed, authorized human approval workflow.

---

## 4. User Roles and Permissions

Implement role-based access control.

Roles:
1. Super Administrator.
2. Network Administrator.
3. Network Engineer.
4. IT Technician.
5. Management / Supervisor.
6. Read-only Viewer.

Permissions must be configurable by module and operation.

Record important actions in an immutable-style audit log, including user, timestamp, action, target record and result.

---

## 5. Suggested Database Architecture

Design a normalized MySQL schema.

Include entities for:

- users
- roles
- permissions
- buildings
- locations
- devices
- device_interfaces
- network_links
- network_topologies
- vendors
- integrations
- integration_credentials
- monitoring_checks
- monitoring_metrics
- monitoring_alerts
- wan_connections
- vlans
- subnets
- ip_addresses
- checklist_templates
- checklist_template_items
- checklist_schedules
- checklist_runs
- checklist_run_items
- maintenance_schedules
- maintenance_records
- incidents
- incident_updates
- problem_records
- change_requests
- change_approvals
- configuration_backups
- knowledge_articles
- notifications
- report_jobs
- audit_logs

Add appropriate foreign keys, indexes, timestamps, status fields and data retention strategies.

Do not place every historical metric into a single unbounded database table without considering storage and query performance.

---

## 6. Backend Architecture

Organize the Go backend into clear modules.

Suggested structure:

- cmd/
- internal/api/
- internal/auth/
- internal/users/
- internal/inventory/
- internal/topology/
- internal/checklists/
- internal/maintenance/
- internal/incidents/
- internal/monitoring/
- internal/integrations/
- internal/firewall/
- internal/wireless/
- internal/network/
- internal/wan/
- internal/changes/
- internal/reports/
- internal/notifications/
- internal/ai/
- internal/database/
- internal/workers/
- migrations/
- config/

Provide:
- API versioning.
- Request validation.
- Consistent error responses.
- Pagination and filtering.
- Authentication and authorization middleware.
- Structured logging.
- Health endpoints.
- Background job management.
- Integration timeout and retry policies.

Document every API endpoint.

---

## 7. Frontend Architecture

Create a modern professional Angular application.

Main navigation:

- Dashboard
- Network Inventory
- Network Topology
- Monitoring
- WAN Management
- Firewall
- Wireless
- VLAN & IPAM
- Daily Checklist
- Preventive Maintenance
- Incidents
- Change Management
- Configuration Backup
- Knowledge Base
- Reports
- Notifications
- AI Assistant
- Administration
- System Settings

Design requirements:
- Professional enterprise NOC appearance.
- Modern dashboard cards.
- Interactive network topology.
- Charts and historical graphs.
- Search and advanced filters.
- Data tables with pagination.
- Responsive design.
- Light and dark themes.
- Accessible forms and status indicators.
- Consistent color semantics.
- Loading, error and empty states.
- English-first interface with internationalization support for Khmer.

Do not create a visually impressive dashboard filled with fake data. Clearly distinguish demo data from real monitoring data.

---

## 8. Security Requirements

Security is mandatory.

Implement:
- Secure authentication.
- Password hashing.
- Role-based permissions.
- Secure API credentials storage.
- Encryption of integration secrets.
- HTTPS deployment.
- Input validation.
- SQL injection protection.
- Rate limiting.
- Session security.
- Audit logging.
- Secure file uploads.
- API credential rotation support.

Never expose API keys, passwords, tokens or sensitive network configurations to frontend logs or browser storage.

Do not commit real credentials into Git.

---

## 9. Deployment and Operations

Create a deployment solution suitable for Ubuntu Server running inside Proxmox.

Provide:
- Dockerfiles.
- Docker Compose.
- MySQL configuration.
- Database migration commands.
- Environment variable examples.
- Persistent storage configuration.
- Application health checks.
- Backup and restore procedures.
- Logging and monitoring.
- Production deployment instructions.

The system must support deployment on an isolated internal network.

Do not expose the management interface publicly by default.

---

## 10. Testing and Quality Assurance

Implement:

Backend:
- Unit tests.
- API integration tests.
- Database tests.
- Authentication tests.
- Monitoring worker tests.
- Vendor adapter tests.

Frontend:
- Component tests.
- Service tests.
- Form validation tests.
- Authentication and authorization tests.

End-to-end:
- Login.
- Device registration.
- Checklist execution.
- Incident creation.
- Maintenance scheduling.
- Report generation.
- Notification delivery.

Include sample integration tests using mocked vendor APIs.

Never depend on access to real RAC devices to run the basic automated test suite.

---

## 11. Development Strategy

Build the product incrementally.

**Phase 1 — Foundation**
- Project structure.
- Authentication.
- User and role management.
- Buildings and locations.
- Device inventory.
- Database migrations.
- Core Angular layout.

**Phase 2 — Network Operations**
- Daily checklists.
- Maintenance management.
- Incident management.
- Change management.
- Knowledge base.

**Phase 3 — Monitoring**
- ICMP monitoring.
- SNMP monitoring.
- Syslog collection.
- WAN health monitoring.
- Alerts and notifications.
- Monitoring dashboards.

**Phase 4 — Vendor Integrations**
- Palo Alto PA-410.
- Meraki Dashboard API.
- UniFi Network API.
- Proxmox API.
- Synology monitoring.
- Cisco SNMP monitoring.
- Linux server monitoring.

**Phase 5 — Analytics and Reports**
- Daily, weekly and monthly reports.
- Performance analysis.
- Historical data.
- Export and scheduling.

**Phase 6 — Intelligent NOC**
- AI incident analysis.
- AI root cause suggestions.
- Knowledge base assistant.
- Automated report generation.
- Alert correlation.

---

## 12. Instructions to Codex

Follow these implementation rules:

1. First inspect the existing repository, files, dependencies and architecture.
2. Do not overwrite existing work without understanding it.
3. Create an architecture document and implementation roadmap.
4. Define the database schema and migration strategy before implementing business modules.
5. Build working software, not just mockups or documentation.
6. Implement features incrementally.
7. Complete and test one module before moving to the next.
8. Use clean, maintainable and modular code.
9. Follow Go and Angular best practices.
10. Do not invent vendor API capabilities. Verify official documentation and supported endpoints before implementing integrations.
11. Use mocked adapters where real credentials or devices are unavailable.
12. Never hardcode credentials.
13. Never deploy destructive configuration changes automatically.
14. Add meaningful error handling and application logs.
15. Keep README documentation updated.
16. Provide database migration and rollback instructions.
17. Ensure Docker Compose can start the development environment.
18. Do not claim a feature is complete until it has been implemented and tested.

At the end of every development phase, report:

- Features implemented.
- Files created or modified.
- Database changes.
- API endpoints created.
- Tests executed and their results.
- Known limitations.
- Instructions to run and verify the feature.
- Remaining tasks.

**Final objective:**

Deliver a production-oriented, secure, scalable Network Operations Management Platform that becomes the central working system for RAC's internal Network and IT team.

The platform must make it possible to answer:

- Is our network healthy?
- Which devices are offline?
- Is the Internet stable?
- What work must I complete today?
- What maintenance is due?
- What incidents are unresolved?
- What configuration changes were made?
- What happened during the last network outage?
- What should the Network Engineer investigate next?

Build the system around real network operations, measurable results, maintainability and future AI-NOC capabilities.
