# Release Execution Checklist

Use this checklist for preparing, deploying, and verifying every application release.

---

## 1. Pre-Release Stage (Development / Staging)

- [ ] All automated tests pass (`php artisan test`).
- [ ] Static asset build completes cleanly (`npm run build`).
- [ ] Git diff check is clean with zero syntax or formatting errors (`git diff --check`).
- [ ] All new migrations reviewed: confirmed 100% additive, non-destructive, with safe nullable/defaults.
- [ ] `VERSION` file updated to the target release version (e.g. `1.0.0`).
- [ ] `CHANGELOG.md` updated with categorized changes under the release header.
- [ ] Dependency diff reviewed (`composer.lock` / `package.json`).
- [ ] Release metadata completed using `docs/RELEASE_TEMPLATE.md`.
- [ ] Upgrade simulated against production-like backup clone.

---

## 2. Production Deployment Stage (Live Server)

- [ ] Current deployed version recorded.
- [ ] Pre-upgrade backup executed and verified (archive $> 0$ bytes, checksum valid).
- [ ] Local production `.htaccess` stashed safely.
- [ ] Correct release tag checked out / pulled cleanly.
- [ ] Local production `.htaccess` restored cleanly.
- [ ] `composer install --no-dev --prefer-dist --optimize-autoloader` executed (if lock changed).
- [ ] Database migrations executed forward-only (`artisan migrate --force`).
- [ ] Application caches purged and rebuilt (`optimize:clear`, `config:cache`, `route:cache`, `view:cache`).

---

## 3. Post-Deployment Verification Stage (Smoke Test)

- [ ] Health endpoint `/up` returns HTTP 200 OK.
- [ ] Admin login succeeds; Admin Dashboard displays active operational summary.
- [ ] Employee Portal login succeeds; Attendance selfie camera and GPS geofence functional.
- [ ] Existing attendance history and reports remain 100% intact and readable.
- [ ] Settings and backup management interfaces fully accessible.
- [ ] Cron / Scheduler executing without overlap or errors.
- [ ] Deployed version recorded in deployment log.
