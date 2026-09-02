# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
