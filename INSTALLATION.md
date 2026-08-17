# Panduan Instalasi Production

Panduan ini menjelaskan instalasi **Attendance / Absen Selon Beauty** di Shared Hosting dan VPS. Aplikasi menggunakan Laravel 13, MySQL/MariaDB, PWA, geofence berbasis Outlet, selfie wajib (bila diaktifkan), dan deteksi keberadaan wajah lokal di browser.

Bagian utama ditujukan untuk pengguna cPanel, DirectAdmin, Plesk, atau Shared Hosting lain yang menyediakan SSH. Ganti semua placeholder seperti `<USERNAME>` dan `<DOMAIN>` dengan nilai hosting Anda.

> **Penting:** jangan pernah menjalankan `migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe` pada production. Perintah tersebut dapat menghapus data.

## Ringkasan Arsitektur Production

- PHP minimum **8.3** berdasarkan `composer.json`; PHP **8.4 direkomendasikan**.
- Document Root domain wajib menunjuk ke `<PROJECT_ROOT>/public`, bukan root repository.
- `public/build` sudah berisi aset Vite production. Instalasi normal tidak memerlukan Node.js.
- MediaPipe, model BlazeFace, dan WASM tersedia lokal; tidak menggunakan CDN/cloud face API.
- Data selfie attendance/overtime dan attachment leave disimpan private dan dilayani melalui controller berizin.
- Geofence karyawan berasal dari `Employee -> outlet_id -> Outlet`.
- Scheduler Laravel menjalankan scheduled backup. Queue production default menggunakan `sync`.

## Persyaratan Hosting

### Software

| Komponen | Persyaratan |
|---|---|
| PHP | Minimum 8.3; direkomendasikan 8.4 |
| Framework | Laravel 13 (`laravel/framework ^13.8`) |
| Database | MySQL 8.0+ atau MariaDB 10.5+ |
| Composer | Composer 2.x |
| Web server | Apache dengan rewrite, LiteSpeed, atau Nginx |
| HTTPS | Wajib untuk camera, geolocation, PWA, dan service worker |
| SSH | Direkomendasikan agar instalasi/update aman |

### Extension PHP

Aktifkan extension berikut pada **PHP web dan PHP CLI**:

- Wajib untuk aplikasi: `pdo`, `pdo_mysql`, `openssl`, `fileinfo`, `gd`, `mbstring`.
- Wajib/umum untuk Laravel dan Composer: `dom`, `libxml`, `xml`, `xmlwriter`, `phar`, `iconv`, `filter`, `session`, `tokenizer`.
- Direkomendasikan: `curl`, `zip`, `intl`, `bcmath`.

`gd` wajib karena selfie divalidasi, diproses ulang, dan dikompresi di server. `zip` direkomendasikan untuk backup ZIP; aplikasi mempunyai fallback arsip ketika `ZipArchive` tidak tersedia. `json`, `hash`, dan `pcre` sudah menjadi bagian PHP modern dan biasanya tidak muncul sebagai toggle terpisah.

Cek PHP CLI:

```bash
<PHP_BINARY> -v
<PHP_BINARY> -m
```

Pastikan versi dan extension CLI sama dengan PHP domain di panel hosting.

## Shared Hosting Installation

Contoh layout yang direkomendasikan:

```text
/home/<USERNAME>/apps/attendance             # <PROJECT_ROOT>
/home/<USERNAME>/apps/attendance/public      # Document Root domain
```

### 1. Buat domain atau subdomain

1. Buat domain/subdomain, misalnya `attendance.example.com`.
2. Pilih PHP 8.4 melalui MultiPHP Manager, Select PHP Version, PHP Settings, atau menu sejenis.
3. Aktifkan extension yang tercantum di atas.

Document Root akan diarahkan ke folder `public` setelah repository selesai dipasang pada langkah 9.

### 2. Aktifkan HTTPS

Aktifkan AutoSSL atau Let's Encrypt sebelum menguji aplikasi. Camera, GPS, service worker, dan instalasi PWA memerlukan secure browser context pada production. `localhost` hanyalah pengecualian untuk development.

### 3. Buat database MySQL

Melalui panel hosting:

1. Buat database `<DB_NAME>`.
2. Buat user `<DB_USER>` dengan password kuat `<DB_PASSWORD>`.
3. Hubungkan user ke database dan berikan **All Privileges**.
4. Catat `DB_HOST`; sebagian hosting menggunakan `localhost`, sebagian memberi hostname khusus.

### 4. Clone repository

```bash
cd /home/<USERNAME>/apps
git clone https://github.com/zaivnet/Selon-Beauty.git attendance
cd attendance
```

Untuk repository private, gunakan SSH Deploy Key read-only. Jangan menaruh Personal Access Token atau password di command, file repository, atau dokumentasi.

### 5. Buat dan isi `.env`

```bash
cd <PROJECT_ROOT>
cp .env.example .env
```

Edit `.env` melalui editor SSH atau File Manager. Contoh minimal production:

```env
APP_NAME="Attendance & Scheduling"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://<DOMAIN>

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<DB_NAME>
DB_USERNAME=<DB_USER>
DB_PASSWORD=<DB_PASSWORD>

SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USERNAME>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Sesuaikan SMTP dengan provider. Jika email belum tersedia saat instalasi awal, Anda dapat memakai `MAIL_MAILER=log`; email akan ditulis ke log dan tidak dikirim.

Jangan memasukkan secret production ke Git.

### 6. Install dependency Composer

Node.js tidak dibutuhkan untuk instalasi production normal karena `public/build` dan aset MediaPipe sudah disertakan di repository. Dependency PHP tetap wajib:

```bash
cd <PROJECT_ROOT>
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Jika Composer berupa file PHAR atau harus dijalankan dengan binary PHP tertentu:

```bash
<PHP_BINARY> <COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Contoh `<COMPOSER_BINARY>` adalah `/home/<USERNAME>/bin/composer.phar`.

Instalasi normal **tidak memerlukan `--no-scripts`**. Script Composer menjalankan Laravel package discovery. Jika provider memaksa instalasi tanpa script, jalankan package discovery setelah vendor selesai:

```bash
<PHP_BINARY> <COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
<PHP_BINARY> artisan package:discover --ansi
```

Gunakan pola ini hanya bila script Composer memang diblokir oleh hosting.

### 7. Generate APP_KEY

```bash
<PHP_BINARY> artisan key:generate
```

Jalankan satu kali saat instalasi pertama.

> **Jangan generate ulang `APP_KEY` pada aplikasi production yang sudah berjalan.** Data terenkripsi, cookie, dan session lama dapat menjadi tidak valid.

### 8. Jalankan migration

Pastikan `.env` menunjuk ke database yang benar, lalu jalankan:

```bash
<PHP_BINARY> artisan migrate --force
```

Tidak diperlukan seeder atau kredensial admin default.

> **Larangan production:** jangan menjalankan `migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe`.

### 9. Konfigurasi Document Root

Setelah source tersedia, arahkan domain ke:

```text
/home/<USERNAME>/apps/attendance/public
```

Lokasi pengaturan umumnya:

- cPanel: **Domains** → **Document Root**.
- DirectAdmin: **Domain Setup** atau pengaturan document root domain.
- Plesk: **Domains** → **Hosting Settings** → **Document root**.

> **Jangan menunjuk Document Root ke `/home/<USERNAME>/apps/attendance`.** File `.env`, source code, dan storage tidak boleh dapat diakses langsung dari web.

Jika panel tidak mengizinkan Document Root di luar `public_html`, hubungi provider agar domain diarahkan ke folder `public`. Jangan memindahkan hanya `index.php` tanpa memahami perubahan path Laravel.

### 10. Atur permission

Laravel harus dapat menulis ke `storage` dan `bootstrap/cache`:

```bash
chmod -R 775 storage bootstrap/cache
```

Permission yang tepat bergantung pada user/group web server hosting. Jangan menggunakan `chmod 777` sebagai default. Jika masih gagal, minta provider menyamakan ownership proses PHP dengan akun hosting.

### 11. Buat storage link

```bash
<PHP_BINARY> artisan storage:link
```

Link ini digunakan untuk file pada disk public, termasuk foto profil karyawan. Branding custom dilayani melalui route `/branding/{type}` sehingga tidak bergantung pada URL storage langsung.

`storage:link` **tidak** membuat selfie attendance/overtime atau attachment leave menjadi public. Evidence tersebut berada di private storage dan tetap dilayani melalui controller dengan authorization.

Jika shared hosting melarang symbolic link, tanyakan provider mengenai dukungan symlink. Aplikasi utama tetap berjalan, tetapi foto profil dari disk public tidak akan tampil melalui `/storage` tanpa link tersebut.

### 12. Bersihkan dan buat cache Laravel

Jalankan setelah `.env` final:

```bash
<PHP_BINARY> artisan optimize:clear
<PHP_BINARY> artisan config:cache
<PHP_BINARY> artisan route:cache
<PHP_BINARY> artisan view:cache
```

### 13. Konfigurasi Cron scheduler

Tambahkan Cron setiap menit:

```cron
* * * * * cd <PROJECT_ROOT> && <PHP_BINARY> artisan schedule:run >> /dev/null 2>&1
```

Contoh CloudLinux:

```cron
* * * * * cd /home/<USERNAME>/apps/attendance && /opt/alt/php84/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Scheduler saat ini menjalankan command scheduled backup setiap menit; command hanya membuat backup saat fitur aktif dan jadwal pada aplikasi cocok. Tanpa Cron, **scheduled backup otomatis tidak berjalan**. Backup manual tetap dapat dijalankan dari aplikasi.

Karena `.env.example` memakai `QUEUE_CONNECTION=sync`, instalasi production standar tidak memerlukan queue worker atau Supervisor. Event dan notification diproses dalam request yang sama.

### 14. Jalankan First-Run Setup

Buka:

```text
https://<DOMAIN>/setup
```

Root URL juga akan mengarahkan ke setup selama sistem belum diinisialisasi. Buat akun Superadmin pertama melalui form. Tidak ada akun atau password default dan tidak perlu menjalankan seeder.

### 15. Konfigurasi aplikasi setelah setup

Setelah login sebagai Superadmin:

1. Buka menu **Outlet**.
2. Buat Outlet pertama dan isi:
   - nama;
   - kode;
   - alamat;
   - latitude;
   - longitude;
   - radius absensi (`radius_meters`);
   - maksimal akurasi GPS (`max_accuracy_meters`);
   - status aktif.
3. Buka **Pengaturan → Pengaturan Absensi** untuk aturan global:
   - timezone;
   - kewajiban geofence saat checkout;
   - kewajiban selfie saat check-in/check-out.
4. Buat Shift dan jadwal kerja.
5. Buat Owner/Admin sesuai kebutuhan.
6. Tetapkan Admin ke Outlet.
7. Admin membuat Employee untuk Outlet miliknya.
8. Uji check-in dan check-out memakai akun Employee.

> **Tidak ada global attendance location. Outlet adalah Single Source of Truth geofence karyawan.** Jangan mencari atau mengonfigurasi menu legacy “Lokasi Presensi”.

### 16. Catatan multi-outlet

- Satu toko: buat satu Outlet.
- Beberapa toko: buat Outlet tambahan.
- Setiap Outlet mempunyai koordinat GPS, radius, dan batas akurasi sendiri.
- Admin dapat ditetapkan ke Outlet.
- Employee yang dibuat Admin otomatis mengikuti Outlet Admin tersebut sesuai aturan akses aplikasi.

## CloudLinux / Alt-PHP

Pada CloudLinux, perintah `php` di SSH dapat menunjuk ke versi lama atau tidak memuat extension yang aktif pada web. Gunakan binary yang diberikan provider, misalnya:

```bash
/opt/alt/php84/usr/bin/php -v
/opt/alt/php84/usr/bin/php -m
/opt/alt/php84/usr/bin/php artisan about
```

Contoh environment yang perlu memuat extension CLI secara eksplisit:

```bash
/opt/alt/php84/usr/bin/php \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/pdo.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/pdo_mysql.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/mbstring.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/fileinfo.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/dom.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/phar.so \
  artisan migrate --force
```

Ini **hanya contoh** untuk CloudLinux yang tidak otomatis memuat extension CLI. Lokasi binary dan module harus disesuaikan dengan provider. Jangan menambahkan `-d extension=...` bila extension sudah dimuat karena dapat menghasilkan peringatan duplicate module.

Untuk Composer PHAR:

```bash
/opt/alt/php84/usr/bin/php <COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

### Membuat PHP Wrapper Agar Perintah Lebih Pendek

Wrapper bersifat opsional. Buat `~/bin/php84-attendance`:

```sh
#!/bin/sh
exec /opt/alt/php84/usr/bin/php \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/pdo.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/pdo_mysql.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/mbstring.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/fileinfo.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/dom.so \
  -d extension=/opt/alt/php84/usr/lib64/php/modules/phar.so \
  "$@"
```

Kemudian:

```bash
chmod 700 ~/bin/php84-attendance
~/bin/php84-attendance artisan migrate --force
```

Cron dapat memakai wrapper:

```cron
* * * * * cd /home/<USERNAME>/apps/attendance && /home/<USERNAME>/bin/php84-attendance artisan schedule:run >> /dev/null 2>&1
```

## Aset Frontend, PWA, dan MediaPipe

### Zero-Node production

Repository menyertakan:

- `public/build/manifest.json`;
- bundle JS/CSS/fonts pada `public/build/assets`;
- model `public/build/assets/blaze_face_short_range-*.tflite`;
- WASM lokal pada `public/vendor/mediapipe/tasks-vision-1.0.0/wasm`.

Karena itu Shared Hosting tidak perlu menjalankan `npm install` atau `npm run build`. Node.js hanya diperlukan ketika developer mengubah source frontend dan ingin membangun ulang aset.

Jangan menghapus `public/build` atau `public/vendor/mediapipe` saat upload/deploy.

### Pemeriksaan MediaPipe

Jika iPhone menampilkan **“DETEKSI WAJAH BERMASALAH”**, periksa aset berikut:

```bash
curl -I https://<DOMAIN>/vendor/mediapipe/tasks-vision-1.0.0/wasm/vision_wasm_internal.wasm
curl -I https://<DOMAIN>/vendor/mediapipe/tasks-vision-1.0.0/wasm/vision_wasm_nosimd_internal.wasm
curl -I https://<DOMAIN>/vendor/mediapipe/tasks-vision-1.0.0/wasm/vision_wasm_internal.js
```

WASM harus memberi `HTTP 200` dan `Content-Type: application/wasm`. Loader JS harus memberi `HTTP 200` dengan MIME JavaScript. Pastikan model BlazeFace yang tercantum pada `public/build/manifest.json` juga dapat diakses.

Deteksi wajah berjalan lokal di browser. Fitur ini hanya memeriksa keberadaan wajah; **bukan face recognition, identity matching, atau liveness verification**.

### Uji PWA setelah instalasi

1. iPhone: Safari → Share → **Add to Home Screen**.
2. Android: Chrome → **Install App** atau **Add to Home Screen**.
3. Verifikasi HTTPS, manifest, service worker, izin GPS, izin camera, selfie, dan face presence detector.

## Update Production dengan Git

### Sebelum update

Gunakan menu **Backup & Restore** aplikasi untuk membuat backup baru, terutama sebelum update besar. Untuk perubahan berisiko tinggi, backup database tambahan juga direkomendasikan.

### Workflow aman

```bash
cd <PROJECT_ROOT>
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
<PHP_BINARY> artisan migrate --force
<PHP_BINARY> artisan optimize:clear
<PHP_BINARY> artisan config:cache
<PHP_BINARY> artisan route:cache
<PHP_BINARY> artisan view:cache
git log -1 --oneline
git status
```

Jika Composer harus memakai PHP khusus:

```bash
<PHP_BINARY> <COMPOSER_BINARY> install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

`git pull --ff-only` mencegah Git membuat merge commit tidak disengaja di server production. Jangan menyimpan perubahan source langsung di server.

`artisan migrate --force` aman untuk migration aplikasi normal yang additive dan akan menampilkan bahwa tidak ada pending migration bila skema sudah terbaru. Jika release secara eksplisit menyatakan **Migration: NONE**, perintah ini boleh dilewati; menjalankannya tetap aman dan tidak menghapus data.

Setelah update, pastikan `public/build` dan `public/vendor/mediapipe` ikut berubah sesuai commit. Tutup lalu buka kembali PWA bila masih menampilkan aset lama.

> Jangan pernah memakai `migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe` sebagai bagian workflow update.

## Backup dan Restore

- Gunakan menu aplikasi **Backup & Restore** untuk backup manual.
- Scheduled backup hanya berjalan jika Cron scheduler aktif dan jadwal backup di aplikasi diaktifkan.
- Restore adalah operasi sensitif dan hanya boleh dilakukan role yang berwenang.
- Buat backup baru sebelum restore atau update besar.
- Simpan salinan backup penting di lokasi terpisah dari akun hosting.

## Troubleshooting

| Masalah | Pemeriksaan/solusi |
|---|---|
| 500 Server Error | Periksa log Laravel terbaru, `.env`, permission, dan extension PHP. |
| `Class "DOMDocument" not found` | Aktifkan DOM/XML pada PHP CLI yang menjalankan Artisan/Composer. |
| `Class "Phar" not found` | Aktifkan Phar pada PHP CLI. |
| `Class "PDO" not found` atau driver tidak ditemukan | Aktifkan PDO dan `pdo_mysql`; cek PHP CLI yang digunakan. |
| Composer bekerja di panel tetapi Artisan gagal via SSH | PHP CLI berbeda dari PHP web. Gunakan binary CloudLinux/Alt-PHP yang benar. |
| 403/404 setelah setup | Pastikan Document Root tepat ke `<PROJECT_ROOT>/public` dan rewrite aktif. |
| CSS/JS lama setelah Git pull | Jalankan cache commands, pastikan `public/build` terbaru, lalu reload penuh PWA. |
| PWA masih memakai versi lama | Tutup penuh dan buka kembali PWA agar service worker memperbarui static cache. |
| GPS tidak tersedia/akurat | Aktifkan HTTPS, izin lokasi, dan precise location; cek koordinat dan batas akurasi Outlet. |
| Camera tidak tersedia | Aktifkan HTTPS dan izin camera untuk domain/PWA. |
| Face detector error | Cek WASM SIMD/non-SIMD, loader JS, model BlazeFace, status HTTP, dan MIME seperti di atas. Catat kode `FD-*` yang tampil. |
| Database connection refused | Cek `DB_HOST`, port, nama database, user, password, dan privileges. |
| Storage permission error | Pastikan `storage` dan `bootstrap/cache` writable oleh proses PHP. |
| Foto profil tidak tampil | Jalankan `storage:link` dan pastikan symlink diizinkan hosting. |
| Scheduled backup tidak berjalan | Periksa Cron setiap menit, PHP binary CLI, dan pengaturan scheduled backup dalam aplikasi. |

### Melihat log Laravel terbaru

Konfigurasi default memakai daily log:

```bash
ls -lah storage/logs
tail -n 100 storage/logs/laravel-$(date +%F).log
```

Jika instalasi masih memakai single log:

```bash
tail -n 100 storage/logs/laravel.log
```

Jangan membagikan log yang berisi secret atau data pribadi ke kanal publik.

## Checklist Shared Hosting

- [ ] PHP 8.4 dipilih (minimum 8.3).
- [ ] Extension PHP web dan CLI aktif.
- [ ] Database, user, dan privileges dibuat.
- [ ] Source di-clone dari repository resmi.
- [ ] `.env` production dikonfigurasi.
- [ ] Composer dependencies terpasang.
- [ ] `APP_KEY` dibuat satu kali.
- [ ] Migration berhasil.
- [ ] Document Root menunjuk ke `/public`.
- [ ] HTTPS aktif.
- [ ] `storage` dan `bootstrap/cache` writable.
- [ ] Storage link dibuat bila didukung.
- [ ] Cache Laravel dibuat setelah `.env` final.
- [ ] Cron scheduler dikonfigurasi.
- [ ] `/setup` selesai dan Superadmin dibuat.
- [ ] Outlet pertama dan geofence dikonfigurasi.
- [ ] Shift, Admin, dan Employee dibuat.
- [ ] GPS diuji pada perangkat sebenarnya.
- [ ] Camera/selfie/face presence diuji.
- [ ] PWA diuji di iPhone atau Android.

## Instalasi VPS / Cloud Server

Untuk VPS, gunakan alur yang sama dengan beberapa tambahan administrasi server:

1. Install PHP 8.4, extension yang diwajibkan, Composer 2, MySQL/MariaDB, dan Apache/Nginx.
2. Clone repository ke lokasi seperti `/var/www/attendance`.
3. Buat database dan `.env`, lalu jalankan Composer, `key:generate`, dan `migrate --force`.
4. Set owner/group agar web server dapat menulis ke `storage` dan `bootstrap/cache`.
5. Atur virtual host root ke `/var/www/attendance/public`.
6. Aktifkan SSL Let's Encrypt.
7. Tambahkan Cron `schedule:run` setiap menit dengan user aplikasi.
8. Buka `/setup`, buat Superadmin, lalu konfigurasikan Outlet.

Queue worker persisten tidak diperlukan selama `QUEUE_CONNECTION=sync`. Jika konfigurasi queue diubah ke database/Redis pada masa mendatang, worker harus dikelola sesuai konfigurasi tersebut.
