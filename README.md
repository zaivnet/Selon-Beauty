# Attendance / Absen Selon Beauty

Aplikasi Laravel 13 untuk presensi, penjadwalan, multi-outlet, izin/cuti, lembur, audit, rekap bulanan, dan backup. Antarmuka employee berbentuk PWA yang dapat dipasang di iPhone dan Android.

## Fitur Utama

- **Multi-Outlet & Geofence:** setiap Employee mengikuti koordinat, radius, dan batas akurasi Outlet miliknya.
- **Presensi GPS & Selfie:** validasi server-side untuk GPS dan selfie check-in/check-out sesuai pengaturan global.
- **Face Presence Detection Lokal:** native browser fast path dan fallback MediaPipe lokal untuk memastikan terdapat wajah pada foto. Fitur ini **bukan face recognition, identity matching, atau liveness verification**.
- **Shift & Kalender Kerja:** jadwal, holiday, schedule override, shift swap, dan cross-midnight.
- **Leave & Overtime:** approval izin/sakit/cuti, overtime request, session, serta recovery admin.
- **Audit & Correction:** perubahan sensitif memiliki audit trail; evidence original dipertahankan.
- **Rekap Kehadiran Bulanan:** data payroll-ready dalam menit, CSV, print, dan indikator review tanpa menghitung nominal gaji.
- **Operational Exception Center:** kondisi yang membutuhkan perhatian admin saat ini.
- **Attendance Participation:** role aplikasi terpisah dari kewajiban mengikuti workforce attendance.
- **Backup & Restore:** backup manual dan scheduled backup melalui Laravel scheduler.
- **Zero-Node Production:** build Vite, model BlazeFace, dan WASM MediaPipe tersedia di repository.

## Requirements

- PHP minimum 8.3; PHP 8.4 direkomendasikan.
- Laravel 13 (`laravel/framework ^13.8`).
- MySQL 8.0+ atau MariaDB 10.5+.
- Composer 2.x.
- HTTPS dan Document Root yang menunjuk ke `<PROJECT_ROOT>/public`.

Lihat daftar extension dan detail hosting pada [INSTALLATION.md](INSTALLATION.md).

## Shared Hosting Quick Start

```bash
cd /home/<USERNAME>/apps
git clone https://github.com/zaivnet/Selon-Beauty.git attendance
cd attendance

cp .env.example .env
# Edit APP_URL, database, mail, dan konfigurasi production pada .env

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Kemudian:

1. Arahkan Document Root domain ke `/home/<USERNAME>/apps/attendance/public`.
2. Aktifkan HTTPS.
3. Tambahkan Cron `* * * * * cd <PROJECT_ROOT> && <PHP_BINARY> artisan schedule:run >> /dev/null 2>&1`.
4. Buka `https://<DOMAIN>/setup` untuk membuat Superadmin pertama.
5. Buat Outlet pertama, lalu konfigurasikan Shift, Admin, dan Employee.

Node.js tidak diperlukan pada hosting production selama aset repository `public/build` dan `public/vendor/mediapipe` tersedia.

> Panduan lengkap untuk cPanel, DirectAdmin, Plesk, CloudLinux/Alt-PHP, troubleshooting, PWA, backup, dan VPS tersedia di [INSTALLATION.md](INSTALLATION.md).

## Update Production

Buat backup melalui aplikasi sebelum update besar, kemudian:

```bash
cd <PROJECT_ROOT>
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
git log -1 --oneline
git status
```

Jangan menjalankan `migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe` pada production.

## Dokumentasi

- [INSTALLATION.md](INSTALLATION.md) — instalasi Shared Hosting dan VPS.
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — arsitektur aplikasi.
- [docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) — skema database.
- [docs/RULES.md](docs/RULES.md) — aturan sistem dan pengembangan.
- [docs/PRD.md](docs/PRD.md) — product requirements.
