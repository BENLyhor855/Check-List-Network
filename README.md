# Wi-Fi Operations

A Laravel 13 and Livewire application for a single Wi-Fi operations workspace. This is an **incremental foundation**, not a production-ready or complete implementation of the requested platform.

## What works

- Laravel session authentication with CSRF protection, login rate limiting, account status checks and four role values: super administrator, IT administrator, management and normal user.
- A management overview, inventory report with building/vendor filters and PDF, Excel (.xlsx), CSV and print options, and an administrator area. Administrators can add, edit, enable and disable buildings; search and filter imported APs by provider and building; assign APs to buildings; and view the last 100 recorded administrative changes. The overview distinguishes unavailable status from last recorded status and displays the latest Meraki inventory import time. The single super administrator can create and manage IT administrator, management and normal user accounts.
- Super administrators can store encrypted provider API keys and test a Cisco Meraki Dashboard connection or a UniFi cloud connector connection. Provider keys are not displayed after entry. The UniFi cloud connector requires supported console firmware and site/API permissions; local-controller authentication is not implemented.
- A manual, read-only Meraki inventory import for MR access points in a specified network. It does not delete absent devices or claim to collect device status. The import rejects paginated responses rather than silently accepting an incomplete inventory. Imported devices can be assigned to buildings.

## Not implemented yet

Device status collection, UniFi device import, clients, traffic, SSIDs, charts, historical analytics, traffic/client/availability reports and scheduled reports, notifications, background synchronization, and device control are **not available**. The overview explicitly marks unavailable data instead of reporting fabricated zeros. This foundation has not been tested against real provider accounts or a MySQL deployment. Do not use it to manage production networks without completing the missing modules and a security review.

## Run locally without containers

Requirements: PHP **8.4+** with PDO MySQL, mbstring, XML, ctype, fileinfo, OpenSSL and tokenizer; Composer; and MySQL 8. PHP GD, ZIP and XML extensions are required for spreadsheet exports; PDO SQLite is also required to run tests. Bootstrap CSS is loaded from jsDelivr; an internet connection is needed for that styling.

1. Create an empty MySQL database and an application user with appropriate schema privileges.
2. From the project root:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Set the database host, name, user and password in `.env`. Keep the file and `APP_KEY` private; the key encrypts integration credentials and must be retained securely for restores. Do not commit `.env`.
4. Run `php artisan migrate`, then `php artisan wifi:setup-admin` and enter a password at the prompt (at least 14 characters and at most 72 bytes). The default login is `admin@example.com`. There is no default password; the account cannot be used until this one-time setup completes. If an existing super administrator is already present, migration preserves that account and does not create a new one.
5. Run `php artisan serve --host=127.0.0.1 --port=8000` and open `http://127.0.0.1:8000`.

Run `php artisan test` to execute the automated tests against an in-memory SQLite database. These tests use fake provider responses; they do not contact network devices.

## Deployment and security

The built-in server is for development. For any future production deployment, use HTTPS, set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, protect the application key, use database-backed sessions, provision a proper PHP web server and backups, restrict provider keys to read-only permissions, and complete a security review. Session revocation in user management assumes the database session driver. Keep the application private until the unimplemented authorization, data collection, retention and audit requirements have been reviewed and completed. API calls currently run synchronously with timeouts; queueing and scheduling are not yet implemented.

Provider documentation: [Meraki Dashboard API](https://developer.cisco.com/meraki/api-v1/getting-started/) and [UniFi Network API](https://developer.ui.com/network/v10.6.106/gettingstarted).
