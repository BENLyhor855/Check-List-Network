> Historical record: these results describe a previous implementation and do not verify the current Laravel application. See the root README for current setup.

# Test results — 6 November 2026

Tested the current foundation implementation in the cloud development environment.

| Check | Result |
| --- | --- |
| Go static analysis (`go vet ./...`) | Passed |
| Go unit + MySQL integration suite | 2 tests passed; no skips in the isolated database run |
| Migration application and second application | Passed in a fresh disposable database |
| Angular component suite | 1 test passed |
| Angular production build | Passed with one stylesheet size warning |
| Compose services | MySQL healthy; API/web running; migration/bootstrap exited 0 |
| HTTP smoke via Nginx | Passed |
| Chromium browser workflow | Passed; no browser runtime errors |

Database tests exercised invalid credentials, login, cookie security attributes, user creation/update, building/location/device registration, duplicate rejection, invalid IP rejection, inventory reads, transactional audit records, viewer authorization, logout and session revocation. The test runner created a unique database and removed it after the run. Development inventory was not changed.

Chromium tested invalid login feedback, successful login, session persistence across reload, navigation, required form fields, audit records, a 390-pixel mobile viewport without horizontal page overflow, logout and access rejection afterward. It also checked that browser storage was empty and JavaScript could not read the session cookie. Normal login audit events were recorded by the running application.

The build warning is unchanged: the app component stylesheet is 4.22 kB, exceeding the 4 kB warning threshold by 225 bytes. This does not fail the build.

Added reusable runners: `scripts/test-integration.py` and `scripts/test-browser.cjs`. Commands and prerequisites are documented in README.md. No application code changes were needed for these checks.

Scope limits: these results validate the implemented foundation only. Full inventory creation through the browser, comprehensive access-control/concurrency/load testing, real vendor devices, and the unimplemented checklist, monitoring, maintenance, incident, reporting and AI modules were not tested. These results do not establish production readiness.
