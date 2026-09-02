# Production Upgrade & Installation Guide

This document defines the strict procedures for installing new instances and upgrading live production environments.

---

## 1. Distinction: New Installation vs Existing Upgrade

| Aspect | New Installation | Existing Production Upgrade |
|---|---|---|
| **Objective** | Initialize empty application instance | In-place upgrade of live operational system |
| **Database** | Freshly created empty database | **PRESERVED** (Zero data loss permitted) |
| **Configuration** | Copy `.env.example` $\rightarrow$ generate `APP_KEY` | **PRESERVED** (`.env` and `APP_KEY` unchanged) |
| **Media / Storage** | Fresh empty storage directories | **PRESERVED** (Private selfies, attachments, branding) |
| **Setup Wizard** | Run `/setup` to create first Superadmin | **LOCKED** (Existing users & sessions retained) |
| **Migrations** | Run `php artisan migrate --force` on empty DB | Run `php artisan migrate --force` for new changes only |

---

## 2. Mandatory Pre-Upgrade Safety Contract

Before beginning an upgrade on a live production server, the operator MUST verify:

1. **Target Release**: Target Git release tag (e.g. `v1.0.1`, `v1.1.0`) is identified and reviewed.
2. **Mandatory Backup**: Full system backup (database + private media) is created and verified ($> 0$ bytes).
3. **Environment Integrity**: `.env` exists and contains a valid `APP_KEY`.
4. **PHP Runtime**: Dedicated PHP binary version $\ge 8.3$ is confirmed.
5. **No Local Conflicts**: Local production files (`public/.htaccess`) are safely stashed.

---

## 3. Production Deployment Procedure (Shared Hosting / cPanel)

On shared hosting (such as cPanel / CloudLinux), the default system CLI `php` may differ from the application's required PHP version. Use the configured PHP CLI wrapper path (e.g., `/home/<user>/bin/php84-attendance` or Alt-PHP binary `/opt/cpanel/ea-php84/root/usr/bin/php`).

### Step-by-Step Upgrade Execution:

```bash
# -------------------------------------------------------------
# 1. Navigate to Application Directory
# -------------------------------------------------------------
cd /home/adezaivm/apps/attendance

# -------------------------------------------------------------
# 2. Preserve Hosting-Specific Configuration (.htaccess)
# -------------------------------------------------------------
# cPanel often injects specific PHP handlers into public/.htaccess.
# Stash this file to prevent Git merge conflicts during pull.
git stash push -m "hosting-cpanel-php-handler" -- public/.htaccess

# -------------------------------------------------------------
# 3. Create Pre-Upgrade Safety Backup
# -------------------------------------------------------------
/home/adezaivm/bin/php84-attendance artisan app:run-scheduled-backup

# -------------------------------------------------------------
# 4. Pull Release Source Code
# -------------------------------------------------------------
git fetch --tags
git pull --ff-only origin main
# Or checkout specific release tag:
# git checkout v1.0.0

# -------------------------------------------------------------
# 5. Restore Hosting-Specific Configuration
# -------------------------------------------------------------
git stash pop

# -------------------------------------------------------------
# 6. Install Dependencies (ONLY when composer.lock changes)
# -------------------------------------------------------------
# Case A: If system composer uses PHP >= 8.3:
# composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
# Case B: If using specific PHP binary with composer phar:
# /home/adezaivm/bin/php84-attendance /path/to/composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# -------------------------------------------------------------
# 7. Apply Forward-Only Database Migrations
# -------------------------------------------------------------
/home/adezaivm/bin/php84-attendance artisan migrate --force

# -------------------------------------------------------------
# 8. Initialize Operational Settings (Required for v1.1.0+)
# -------------------------------------------------------------
# Idempotently establishes initial outlet_mode ('multi' or 'single') in app_settings
/home/adezaivm/bin/php84-attendance artisan app:init-outlet-mode

# -------------------------------------------------------------
# 9. Rebuild Application Caches
# -------------------------------------------------------------
/home/adezaivm/bin/php84-attendance artisan optimize:clear
/home/adezaivm/bin/php84-attendance artisan config:cache
/home/adezaivm/bin/php84-attendance artisan route:cache
/home/adezaivm/bin/php84-attendance artisan view:cache

# -------------------------------------------------------------
# 10. Verify Post-Upgrade Status
# -------------------------------------------------------------
git log -1 --oneline
git status
```

---

## 4. Composer & Dependency Guidelines

- **NEVER** run `composer update` on production.
- `composer install` strictly obeys `composer.lock`, ensuring exact packages and versions tested in staging are installed.
- For releases with no dependency changes, Step 6 can be skipped.

---

## 5. Failure & Rollback Protocol

1. **Failure BEFORE Migration**:
   - Abort upgrade.
   - Restore previous Git commit/tag.
   - Rebuild Laravel caches (`artisan optimize:clear && artisan config:cache && artisan route:cache && artisan view:cache`).
2. **Failure AFTER Migration**:
   - **DO NOT** automatically run `artisan migrate:rollback`.
   - Because migrations follow the additive contract, the newer database schema remains compatible with the previous application code.
   - Roll back application code (`git checkout <previous-tag>`), refresh caches, and investigate error logs.
   - Full database restoration from pre-upgrade backup is a disaster recovery action of last resort.
