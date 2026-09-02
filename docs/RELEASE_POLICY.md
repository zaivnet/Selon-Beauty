# Release & Versioning Policy

## 1. Semantic Versioning Specification (SemVer)

This application follows strict Semantic Versioning (`MAJOR.MINOR.PATCH`):

- **PATCH (`v1.0.x`)**: Bug fixes, security patches, visual polish, and non-breaking performance optimizations. No schema migrations allowed unless strictly additive performance indexes.
- **MINOR (`v1.x.0`)**: New backward-compatible features (e.g., new reporting metrics, cross-outlet calendar tools). Forward-only schema migrations allowed.
- **MAJOR (`v2.0.0`)**: Breaking architectural changes, incompatible schema redesigns, or minimum platform requirement upgrades (e.g., PHP version upgrade).

## 2. Immutability of Published Releases

1. **Tag Immutability**:
   - Once a Git tag (e.g. `v1.0.0`) is published, it is **IMMUTABLE**.
   - A published tag must never be deleted, replaced, or force-pushed to another commit hash.
2. **Migration Immutability**:
   - Migration files belonging to a published release are **PERMANENTLY FROZEN**.
   - Developers must never edit, rename, reorder, or delete existing migration files.
   - All future schema changes must be introduced via **NEW** migration files.
3. **Branch Progression**:
   - The `main` branch continues to advance with ongoing development.
   - Production environments may remain pinned to older stable release tags without issue.

## 3. Database Evolution Rules

- **Forward-Only Migrations**: All production migrations must be executed via `php artisan migrate --force`.
- **No Destructive Operations**: Normal releases must never use `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe`.
- **Zero-Downtime Migration Pattern (Expand → Migrate → Switch → Cleanup)**:
  - **Release N (Expand)**: Add new nullable columns or tables. Support dual-read/write if necessary.
  - **Release N+1 (Migrate & Switch)**: Backfill data and switch active application logic to the new structure.
  - **Release Major (Cleanup)**: Drop deprecated columns/tables only after a complete deprecation cycle.
- **No Automatic Rollback**: Database rollbacks (`migrate:rollback`) must **never** be automated in production deployment scripts. If deployment fails, roll back the application code while preserving the forward-compatible database state.
