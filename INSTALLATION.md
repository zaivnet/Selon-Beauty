# Portable Installation & Deployment Guide

Dokumentasi ini menjelaskan langkah-langkah instalasi, konfigurasi, dan pemeliharaan aplikasi berbasis **Laravel 13** pada server Linux/Windows (VPS, Cloud Server, mau pun Shared Hosting cPanel/DirectAdmin/Plesk).

---

## 📋 Syarat Lingkungan (Prerequisites)

- **PHP**: Versi `>= 8.3` (Disarankan **PHP 8.3** atau **PHP 8.4**)
- **PHP Extensions**: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`, `json`, `xml`, `zip`, `intl`, `bcmath`
- **Database Server**: MySQL `>= 8.0` atau MariaDB `>= 10.5` (Charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci`)
- **Composer**: Dependency Manager v2.x
- **Web Server**: Apache (`mod_rewrite` aktif) atau Nginx
- **Web Document Root**: Wajib mengarah ke direktori `<PROJECT_ROOT>/public`

---

## 🚀 Panduan Instalasi Utama (Generic Server / VPS)

### Langkah 1: Clone Repository
```bash
git clone <REPOSITORY_URL> <PROJECT_ROOT>
cd <PROJECT_ROOT>
```

### Langkah 2: Install Dependensi PHP
```bash
<COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader
```

### Langkah 3: Konfigurasi Environment
Salin file template `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Sesuaikan variabel environment pada `.env`:
```env
APP_NAME="Attendance & Scheduling"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<DOMAIN>

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<DB_NAME>
DB_USERNAME=<DB_USER>
DB_PASSWORD=<DB_PASSWORD>
```

### Langkah 4: Generate Application Key
```bash
<PHP_BINARY> artisan key:generate
```

### Langkah 5: Jalankan Database Migration
Jalankan migrasi database pada database kosong:
```bash
<PHP_BINARY> artisan migrate --force
```

### Langkah 6: Konfigurasi Web Server Document Root
Pastikan Web Server (Apache/Nginx/cPanel) mengarahkan domain `https://<DOMAIN>` ke folder:
```text
<PROJECT_ROOT>/public
```

### Langkah 7: First-Run Setup via Browser
1. Buka URL aplikasi di browser: `https://<DOMAIN>/setup`
2. Form **First-Run System Setup** akan muncul otomatis.
3. (Opsional) Masukkan **Nama Aplikasi** dan **Nama Perusahaan**.
4. Masukkan **Nama Lengkap**, **Email**, dan **Password** untuk akun **Superadmin** pertama.
5. Klik **Inisialisasi & Buat Superadmin**.
6. Sistem akan mencatat konfigurasi awal, membuat Superadmin aktif, mengunci halaman `/setup`, dan mengarahkan ke halaman login.

---

## 🌐 Panduan Khusus Shared Hosting (cPanel / DirectAdmin / Plesk)

Jika Anda mendeploy aplikasi pada lingkungan Shared Hosting:

1. **Buat Domain / Subdomain**:
   - Buat domain atau subdomain baru pada cPanel.
   - Atur **Document Root** domain mengarah ke `<PROJECT_ROOT>/public` (bukan root project).

2. **Buat Database MySQL**:
   - Buat database baru (misal: `user_attendance`).
   - Buat MySQL User & Password, lalu *grant all privileges* ke database tersebut.

3. **Clone / Upload Source Code**:
   - Melalui Git Version Control di cPanel / SSH Terminal:
     ```bash
     git clone <REPOSITORY_URL> <PROJECT_ROOT>
     ```

4. **Install Dependensi Composer**:
   ```bash
   cd <PROJECT_ROOT>
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```

5. **Setup `.env` & Migration**:
   ```bash
   cp .env.example .env
   # Edit .env via File Manager atau CLI dengan kredensial database cPanel Anda
   php artisan key:generate
   php artisan migrate --force
   ```

6. **Aktifkan SSL (HTTPS)**:
   - Aktifkan SSL gratis (Let's Encrypt / AutoSSL) pada cPanel.

7. **Proses First-Run Setup**:
   - Buka `https://<DOMAIN>` -> sistem akan mengarahkan ke `/setup`.
   - Buat akun Superadmin pertama Anda.

8. **Konfigurasi Tambahan (Post-Install)**:
   - Login sebagai Superadmin.
   - Atur lokasi toko / titik presensi GPS pada menu **Lokasi Presensi**.
   - Atur jam kerja / shift pada menu **Kelola Shift**.
   - Atur konfigurasi SMTP email pada menu **Pengaturan Branding & Email**.

---

## ⏰ Konfigurasi Cron Task Scheduler

Untuk menjalankan pembersihan session, pengiriman email background, dan tugas terjadwal lainnya, tambahkan perintah Cron Job berikut pada server/cPanel:

```cron
* * * * * cd <PROJECT_ROOT> && <PHP_BINARY> artisan schedule:run >> /dev/null 2>&1
```

*Contoh pada cPanel Cron Jobs:*
```bash
* * * * * cd /home/username/public_html/beauty && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 Prosedur Update Aplikasi (Git Pull Workflow)

Saat ada pembaruan kode versi baru dari GitHub Repository, ikuti langkah-langkah berikut **tanpa menghapus/mereset database**:

```bash
# 1. Masuk ke folder project
cd <PROJECT_ROOT>

# 2. Ambil pembaruan dari repository
git pull origin main

# 3. Update dependensi Composer (jika ada pembaruan paket)
<PHP_BINARY> <COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader

# 4. Jalankan migrasi database baru (aman & non-destructive)
<PHP_BINARY> artisan migrate --force

# 5. Bersihkan & Optimalkan Cache Runtime
<PHP_BINARY> artisan optimize:clear
<PHP_BINARY> artisan config:cache
<PHP_BINARY> artisan route:cache
<PHP_BINARY> artisan view:cache
```

> **⚠️ PENTING**: Dilarang menjalankan `php artisan migrate:fresh` atau `php artisan db:wipe` pada server production karena akan menghapus seluruh data transaksi, data karyawan, dan audit log!

---

## 🔐 Keamanan Repository & Deployment

- **Private / Public Repository**: Source code ini dirancang aman baik disimpan pada Public maupun Private GitHub Repository.
- **Dilarang Commit Secret**: File `.env`, password database, API Key, kredensial SMTP, dan file unggahan pengguna (`storage/app/public`) telah diabaikan oleh `.gitignore`.
- **SSH Deploy Key**: Untuk server production yang mengakses Private Repository, gunakan **Deploy Keys** (SSH) berizin *read-only* pada setting repository GitHub. Jangan menyimpan Personal Access Token secara hardcode.
