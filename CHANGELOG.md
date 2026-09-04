# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-09-04

### Added
- **Feature #1: Dashboard Status Operasional Hari Ini — “Semua Outlet”**:
  - Operational workforce aggregation across all accessible outlets in `DashboardService`.
  - Outlet aggregation respects `OutletScopeService` (Superadmin/Owner aggregate all active outlets, scoped Admins only aggregate assigned outlets).
  - Preserved WORK Outlet semantics for operational shift monitoring and headcount tracking.
  - Correct attribution of temporary cross-outlet assignments to the active operational outlet.
  - Single Outlet Mode displays streamlined summary cards without redundant outlet selector.
- **Feature #2: Reactive Attendance Report Filters**:
  - Live, reactive filtering on the administrative Attendance Report without manual "Filter" or "Reset" buttons.
  - Cascading dropdowns: selecting an outlet dynamically narrows available employee options.
  - Date range changes dynamically recalculate and filter employee relevance.
  - Shared query builder and filter contract ensuring 100% parity across Web view, Print preview, and CSV export.
  - Automatic selection reset when an employee is no longer valid under active outlet/date filters.
- **Feature #3: Automatic Shift Checkout**:
  - Per-shift configurable `auto_checkout_enabled` and `auto_checkout_grace_minutes` controls in Shift Management.
  - New shifts default to Auto Checkout Enabled (`ON`) with a 10-minute grace period.
  - Immutable check-in snapshot population: `scheduled_shift_end_at`, `break_minutes_snapshot`, and `auto_checkout_boundary`.
  - Provenance tracking with `checkout_source` (`manual` vs `auto_shift_end`).
  - Scheduler background task (`attendance:auto-checkout`) executing every minute (`* * * * *`) with overlapping protection (`withoutOverlapping(10)`).
  - Full cross-midnight shift checkout support without date anchor regressions.
  - Preemptive manual checkout preservation: manual checkout prior to boundary safely clears boundary and logs `checkout_source = 'manual'`.
  - Automated in-app notification dispatch on successful auto-checkout.

### Changed
- Shift management interface updated with Auto Checkout toggle and grace minute inputs.
- Attendance report views updated with reactive DOM triggers and real-time state synchronization.
- Attendance lists, detail modals, print templates, and CSV exports display the `Auto (Shift End)` checkout badge.

### Security / Safety
- Scoped Admin authorization enforcement: unauthorized `outlet_id` query tampering on Web, Print, or CSV export endpoints is rejected with `403 Forbidden`.
- Internal scheduler execution: auto-checkout is processed exclusively via protected Artisan CLI command, requiring no unauthenticated HTTP endpoints.
- Server-side provenance protection: `checkout_source` cannot be spoofed by employee client requests.
- No destructive database migration: added nullable/defaulted columns and index with zero column drops, renames, or table locks.
- Zero backfill of legacy data: historical attendance records retain `auto_checkout_boundary = NULL` and are never auto-closed.
- Existing shifts safely migrate with Auto Checkout `OFF` (`auto_checkout_enabled = false`).
- Employee HOME outlet and historical attendance WORK outlet snapshots remain 100% immutable.
- Zero production data reset.

### Upgrade Notes
- Run `php artisan migrate --force` during deployment.
- Rebuild application caches (`optimize:clear`, `config:cache`, `route:cache`, `view:cache`).
- Existing shifts will have Auto Checkout **OFF**; administrators may selectively enable it per shift.
- Standard cron runner (`php artisan schedule:run`) automatically executes `attendance:auto-checkout` every minute.

## [1.1.0] - 2026-09-02

### Added
- Single Outlet and Multi Outlet operating modes.
- Centralized `OutletModeService` capability management.
- Safe explicit outlet mode initialization command (`php artisan app:init-outlet-mode`).
- Single Outlet UI simplification across administrative navigation and management views.
- Automatic HOME Outlet binding for new employees in Single Mode.
- Server-side capability guards preventing second-outlet creation in Single Mode.
- Outlet Mode management for Owner and Superadmin roles.
- Upgrade compatibility test coverage from v1.0.0 to v1.1.0.

### Changed
- Existing installations with multiple active outlets initialize as Multi Outlet.
- Existing installations with exactly one active outlet initialize as Single Outlet.
- Multi-outlet-only controls are hidden when operating in Single Outlet mode.
- Reports, schedules, employee management, and outlet filters adapt dynamically to the selected operating mode.
- Outlet mode resolution is centralized and side-effect-free during normal reads.

### Data Safety
- No database schema migration was introduced.
- No employee outlet IDs are rewritten.
- Attendance outlet snapshots remain unchanged.
- Transfer history remains unchanged.
- Schedule WORK outlet history remains unchanged.
- Geofence coordinates/radius remain unchanged.
- Single → Multi transition requires no data migration.
- Multi → Single transition is protected by server-side blockers.

## [1.0.0] - 2026-09-02

### Added
- **Core Attendance System**: Check-in and check-out tracking with GPS coordinate logging, distance calculation, accuracy validation, and selfie photo evidence capture.
- **Biometric Presence Validation**: Client-side face detection via local MediaPipe Vision WASM bundle with hardware acceleration and Shape Detection API fallback.
- **Multi-Outlet Operations**: Support for multiple physical outlet branches, HOME vs WORK outlet resolution, and temporary cross-outlet assignments.
- **Shift & Work Schedule Management**: Customizable shifts, work calendar, holiday schedules, daily schedule overrides, and shift swap workflows between employees.
- **Leave & Overtime Management**: Employee leave requests with attachment support, realtime overtime session timers, and multi-tier approval workflows.
- **Attendance Corrections & Audit Trail**: Full administrative correction workflows with mandatory reasoning, before/after JSON audit diffs, and historical tracking.
- **Comprehensive Reporting**: Multi-outlet attendance reports, daily duty rosters, monthly recaps, PDF/print exports, and independent work-outlet filtering.
- **Role-Based Access Control (RBAC)**: Distinct permissions and scoped access for Superadmin, Owner, Admin (multi-assigned outlets), and Employee roles.
- **Backup & Disaster Recovery**: Automated and manual full-system backups (database dump, private selfie evidence, leave attachments, and branding assets) with SHA-256 integrity verification.
- **Progressive Web App (PWA)**: Mobile employee portal with offline capabilities, Service Worker caching, and standalone installability.
- **Release Management Foundation**: Centralized `VERSION` resolution and semantic versioning contracts.

### Fixed
- **Work-Outlet Report Decoupling**: Fixed filter coupling in attendance reports to ensure employee selection operates independently per target work outlet.
- **Face Detection Tolerance**: Relaxed detection thresholds on legacy mobile camera sensors (`minConfidence` 0.50, `minWidthRatio` 0.15) to prevent false negative rejections on low-end Android devices.
- **Geofence Simulator Input Source**: Removed default first-outlet coordinate auto-fill from employee test fields and added live device GPS locator button.
- **Admin Search Filter Spacing**: Resolved icon overlap across search input fields in admin monitoring dashboards.
- **Historical Data Integrity**: Preserved relationship references to soft-deleted outlets and deactivated employees across historical duty rosters and reports.

### Security
- **Private Evidence Isolation**: Stored all selfie captures, overtime photos, and leave medical certificates in non-public private storage with authenticated streaming gates.
- **First-Run Setup Lock**: Gated `/setup` endpoint against re-initialization once an active Superadmin user exists.
- **CSRF & Rate Limiting**: Enforced CSRF tokens, secure session cookies (`SESSION_DRIVER=database`), and throttled authentication endpoints.
- **GPS & Biometric Tamper Protection**: Server-side Haversine distance verification and maximum accuracy enforcement to mitigate GPS spoofing.
