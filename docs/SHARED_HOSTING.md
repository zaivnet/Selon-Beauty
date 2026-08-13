# SHARED HOSTING DEPLOYMENT

## 1. Preflight

Catat dari cPanel/hosting:

- PHP version:
- MySQL/MariaDB version:
- HTTPS:
- Cron:
- SSH:
- Composer:
- Document root configurable:
- Storage quota:
- Max upload size:
- Memory limit:

## 2. Framework Compatibility

Target utama:
- Laravel 13 membutuhkan PHP >= 8.3.

Jika hosting di bawah PHP 8.3:
- jangan upgrade framework secara paksa;
- pilih Laravel version yang kompatibel;
- pertahankan arsitektur dan fitur.

## 3. Build Strategy

Di local/dev:
```bash
composer install
npm ci
npm run build
```

Untuk deployment:
- upload source production;
- upload `vendor` jika shared hosting tidak memiliki Composer;
- upload built assets;
- Node.js tidak dibutuhkan saat aplikasi berjalan.

## 4. Document Root Preferred

Ideal:
```text
domain/subdomain document root
    -> /path/to/project/public
```

Jangan expose:
- `.env`;
- `vendor`;
- `storage`;
- source application.

## 5. If Document Root Cannot Point to /public

Jangan langsung memindahkan seluruh isi Laravel ke public_html secara sembarangan.

Buat strategi deployment yang menjaga:
- hanya public assets dan index entry point public;
- application files tetap di luar web root;
- path bootstrap disesuaikan dengan benar.

Validasi pada environment hosting aktual.

## 6. Environment

`.env` production minimal:
```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
...
```

Jangan commit `.env`.

## 7. Writable Directories

Pastikan:
- `storage/`
- `bootstrap/cache/`

writable oleh PHP process.

Jangan memakai permission 777 kecuali benar-benar tidak ada alternatif dan hanya sementara untuk diagnosis.

## 8. Storage

Jika selfie disimpan private:
- jangan mengandalkan `storage:link` untuk mengekspos foto.
- serve via authorized endpoint.

## 9. Cron

Laravel Scheduler:
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Path PHP/artisan menyesuaikan hosting.

## 10. Deployment Commands

Jika SSH tersedia:
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Gunakan `route:cache` hanya bila seluruh route kompatibel.

## 11. HTTPS

Wajib untuk:
- login production;
- geolocation modern;
- camera getUserMedia;
- PWA/service worker.

## 12. Post-Deployment Validation

- login Owner;
- create employee;
- create shift;
- schedule employee;
- location permission;
- camera permission;
- check-in within radius;
- reject outside radius;
- reject poor accuracy;
- duplicate check-in blocked;
- check-out;
- leave request;
- approval;
- report;
- PWA manifest;
- installability;
- responsive;
- APP_DEBUG false;
- private selfie access blocked for unauthorized user.

## 13. Backup

Sebelum setiap migration production:
- database backup;
- backup uploaded attendance/leave files;
- record current release version.

## 14. Backup & Restore Engine Architecture (Shared Hosting Compatible)

- **Engine Detection**: Sistem secara otomatis mendeteksi ketersediaan binary `mysqldump` pada hosting. Jika tidak tersedia atau fungsi `exec()` dibatasi, sistem otomatis beralih ke **Application-Level Logical Export** (Laravel Query Builder JSON chunked export).
- **Private Storage**: Seluruh arsip `.zip` backup disimpan pada direktori private `storage/app/private/backups/` yang tidak dapat diakses secara publik lewat web server.
- **Cron Scheduler**: Pengaturan backup otomatis memanfaatkan Cron Laravel Scheduler (`* * * * * php artisan schedule:run`).
- **Required PHP Extensions**: `GD` / `fileinfo` (untuk upload logo/icon) dan `json` / `hash` (SHA-256 integrity check).
- **Zip Archive Fallback**: Apabila ekstensi `php_zip` (`ZipArchive`) tidak diaktifkan pada shared hosting, sistem memiliki fallback pengarsipan file internal tanpa memutus operasional backup.
- **Pre-Restore Safety Backup**: Setiap kali Owner menjalankan Restore, sistem otomatis membuat *Pre-Restore Safety Backup* terlebih dahulu untuk mencegah kehilangan data akibat kegagalan restore.
