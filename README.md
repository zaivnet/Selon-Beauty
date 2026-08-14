# Portable Attendance & Scheduling Application

Aplikasi Sistem Presensi, Penjadwalan Kerja, dan Manajemen Karyawan Berbasis Web (PWA-Ready) yang Dirancang Portable untuk Berbagai Perusahaan dan Lingkungan Hosting.

---

## 🌟 Fitur Utama

- **PWA (Progressive Web App)**: Mobile-first interface yang dapat di-install di perangkat Android/iOS seperti aplikasi native (offline fallback & shortcut).
- **Presensi Berbasis GPS & Geofencing**: Validasi lokasi GPS waktu nyata saat karyawan melakukan Check-In / Check-Out.
- **Validasi Selfie**: Mengambil foto selfie saat presensi menggunakan kamera bawaan perangkat.
- **Manajemen Shift & Jadwal Kerja**: Jadwal mingguan, kalender hari libur/hari kerja khusus, override per karyawan dan tanggal, serta permohonan lembur dan izin/cuti.
- **Sistem Role & Hak Akses (RBAC)**: Mendukung role **Superadmin**, **Owner**, **Admin Operasional**, dan **Employee**.
- **First-Run Portable Setup**: Wizard inisialisasi awal otomatis saat pertama kali dibuka di browser tanpa perlu SQL seed manual.
- **Keamanan & Email Reset**: Pemulihan password via email (Mailpit support di local, SMTP di production), rate limiting, pembatalan session lama saat reset, dan audit log lengkap.
- **Backup & Restore Terjadwal**: Fitur backup database & media bawaan dari admin portal.
- **Zero-Node Production Deployment**: Pre-compiled Vite assets tersedia di repository, sehingga aplikasi dapat berjalan langsung di Shared Hosting tanpa butuh Node.js di server.

---

## 💻 Spesifikasi & Requirements

- **Language**: PHP `>= 8.3` (Disarankan **PHP 8.3** atau **PHP 8.4**)
- **Framework**: Laravel 13
- **Database**: MySQL `>= 8.0` atau MariaDB `>= 10.5` (`utf8mb4_unicode_ci`)
- **Composer**: Composer v2.x
- **Web Server**: Apache (`mod_rewrite` aktif) atau Nginx
- **Web Document Root**: Mengarah ke direktori `<PROJECT_ROOT>/public`

---

## 🚀 Quick Start (Instalasi Cepat)

```bash
# 1. Clone Repository
git clone https://github.com/zaivnet/Selon-Beauty <PROJECT_ROOT>
cd <PROJECT_ROOT>

# 2. Install Dependensi Composer (Production)
composer install --no-dev --prefer-dist --optimize-autoloader

# 3. Setup File Environment
cp .env.example .env

# 4. Sesuaikan Kredensial Database pada .env, Lalu Generate Application Key & Migrasi
php artisan key:generate
php artisan migrate --force

# 5. Point Document Root Web Server ke: <PROJECT_ROOT>/public

# 6. Buka Browser
https://<DOMAIN>
```

Saat pertama kali dibuka, sistem akan otomatis mengarahkan ke halaman **/setup** untuk membuat akun **Superadmin** pertama serta menentukan nama aplikasi & perusahaan Anda.

> 📖 **Panduan Instalasi Lengkap**: Untuk langkah-langkah detail pada VPS, Cloud Server, maupun **Shared Hosting (cPanel / DirectAdmin / Plesk)**, silakan baca [**INSTALLATION.md**](INSTALLATION.md).

---

## 🔄 Pembaruan Aplikasi (Git Update Workflow)

Untuk memperbarui versi aplikasi dari GitHub Repository **tanpa mereset database**:

```bash
cd <PROJECT_ROOT>
git pull origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ⏰ Task Scheduler (Cron Job)

Tambahkan perintah Cron berikut pada server / cPanel untuk menjalankan tugas terjadwal:

```cron
* * * * * cd <PROJECT_ROOT> && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔒 Catatan Keamanan

- File `.env`, password database, kredensial SMTP, dan file unggahan pengguna diabaikan oleh `.gitignore` dan **TIDAK PERNAH** dimasukkan ke repository.
- Email bersifat unik (*case-insensitive*) untuk seluruh akun user di database level.
- Dilarang menjalankan `php artisan migrate:fresh` atau `php artisan db:wipe` pada lingkungan production.

---

## 📁 Dokumentasi Arsitektur

- [**INSTALLATION.md**](INSTALLATION.md) — Panduan instalasi generik & Shared Hosting
- [`docs/PRD.md`](docs/PRD.md) — Product Requirements Document
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — Arsitektur Sistem
- [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) — Skema Database
- [`docs/RULES.md`](docs/RULES.md) — Aturan Pengembangan Kode
