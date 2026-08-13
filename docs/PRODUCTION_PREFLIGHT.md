# PRODUCTION PREFLIGHT CHECKLIST — SELON BEAUTY Attendance

Checklist verifikasi pra-deployment produksi:

## Database & Environment Checklist
- [x] Database produksi baru / kosong dibuat
- [x] `APP_ENV=production`
- [x] `APP_DEBUG=false`
- [x] Kredensial koneksi database terkonfigurasi dengan aman
- [x] `php artisan migrate --force` berhasil dijalankan (tanpa `--seed` dummy data)
- [x] Tidak ada data dummy karyawan / absensi di database produksi
- [x] Tidak ada default Superadmin credential hardcoded (`admin@admin.com`, `password123`, dll)

## First-Run Setup & Access Control Checklist
- [x] Halaman `/setup` tersedia secara otomatis ketika belum ada Superadmin aktif
- [x] Form setup pertama membuat 1 Superadmin dengan `is_active = true` dan `role = superadmin` (ditentukan server-side)
- [x] Endpoint `/setup` dan `POST /setup` dikunci secara server-side setelah Superadmin pertama dibuat
- [x] Login Superadmin pertama berhasil mengarahkan ke `/admin/dashboard`
- [x] Otentikasi dan perlindungan hirarki role (Superadmin, Owner, Admin, Employee) terverifikasi
- [x] Fitur sensitif Restore dikunci khusus Superadmin
- [x] Protection last active Superadmin aktif

## Password Recovery & SMTP Mail Checklist
- [x] `MAIL_MAILER` terkonfigurasi (`smtp`)
- [x] `MAIL_HOST` terkonfigurasi
- [x] `MAIL_PORT` terkonfigurasi (misal: `587` / `465`)
- [x] `MAIL_USERNAME` terkonfigurasi
- [x] `MAIL_PASSWORD` terkonfigurasi dengan aman
- [x] `MAIL_ENCRYPTION` terkonfigurasi (`tls` / `ssl`)
- [x] `MAIL_FROM_ADDRESS` terkonfigurasi
- [x] `MAIL_FROM_NAME="${APP_NAME}"`
- [x] `APP_URL` terkonfigurasi dengan HTTPS produksi
- [x] Pengiriman email tes berhasil (`php artisan selon:test-mail recipient@example.com`)
- [x] Email reset password dikirimkan secara synchronous/aman tanpa membocorkan kredensial
- [x] Link reset password menggunakan domain produksi yang benar
- [x] Token reset bersifat single-use, expired 60 menit, dan mencabut sesi lama pengguna

## Asset & Performance Checklist
- [x] `npm run build` berhasil dikompilasi tanpa error
- [x] Dynamic PWA Manifest dan Service Worker berfungsi tanpa error (halaman reset password tidak dicache SW)
- [x] Cache produksi diinisialisasi (`php artisan config:cache`, `route:cache`, `view:cache`)
- [x] Shared hosting compatibility terverifikasi (tidak memerlukan Redis, Supervisor, PM2, WebSocket, atau Docker)
