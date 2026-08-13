# PROJECT CONTEXT — SELON BEAUTY Attendance

## Identitas

Nama aplikasi sementara: **SELON BEAUTY Attendance**

Bisnis: **SELON BEAUTY**

Kondisi awal:
- 1 toko.
- 4 karyawan.
- Aplikasi digunakan Owner/Admin dan Karyawan.
- Target deployment adalah shared hosting.
- Aplikasi harus tetap nyaman jika jumlah karyawan bertambah.

## Masalah yang Diselesaikan

Owner membutuhkan satu aplikasi untuk:
- menyusun jadwal kerja;
- mengetahui siapa yang masuk hari ini;
- memonitor keterlambatan;
- memonitor pulang lebih awal;
- memverifikasi lokasi absensi;
- melihat selfie check-in/check-out;
- memproses izin, sakit, dan cuti;
- melihat laporan kehadiran.

Karyawan membutuhkan aplikasi yang mudah digunakan dari HP untuk:
- melihat shift;
- absen masuk;
- absen pulang;
- melihat riwayat;
- mengajukan izin/sakit/cuti;
- melihat status pengajuan.

## Prinsip Produk

1. Mobile-first untuk karyawan.
2. Desktop-first tetapi tetap responsive untuk Owner/Admin.
3. Satu aksi utama per layar mobile.
4. Tidak ada data dummy pada production.
5. Tidak ada fitur yang hanya terlihat bekerja tetapi belum tersambung ke database.
6. Jangan membuat tombol mati.
7. Semua status harus mempunyai sumber data dan aturan yang jelas.
8. Gunakan teknologi yang realistis untuk shared hosting.
9. Hindari overengineering.
10. Keamanan dan auditability harus dibangun sejak awal.

## Role & Akses Aplikasi

### Superadmin
Akses penuh sistem, manajemen Owner/Admin/Employee, Restore Backup, Branding, Backup/Schedule, Audit Logs, CLI administration (`php artisan selon:create-superadmin`).

### Owner
Akses pengelolaan toko, manajemen Admin/Employee, Branding, Backup Create/Download/Schedule, Audit Logs. DILARANG melakukan Restore Backup atau menaikkan privilege ke Superadmin/Owner.

### Admin
Akses operasional harian toko: karyawan, jabatan, lokasi toko, shift, jadwal, monitoring absensi, koreksi absensi, izin/cuti, lembur, dan laporan. DILARANG mengelola Role, Restore Backup, Backup Create/Download, Branding, atau Audit Logs.

### Employee
Hanya akses portal karyawan: dashboard pribadi, jadwal pribadi, check-in/check-out, riwayat pribadi, pengajuan izin/cuti/lembur pribadi, profil pribadi.

### Prinsip Utama: Jabatan != Role Aplikasi
Jabatan (`job_titles.name`) adalah posisi operasional kerja (contoh: Kasir, Stylist, Admin Toko). Jabatan TIDAK menentukan hak akses aplikasi. Hak akses ditentukan secara eksklusif oleh `users.role`.

## First-Run Superadmin Setup & Zero Default Credentials

Aplikasi mengimplementasikan mekanisme **First-Run Setup** (`/setup`):
- Fresh installation dan production deployment dimulai dengan **0 default credentials** dan **0 dummy business data**.
- Akses awal ke aplikasi mengarahkan pengguna ke `/setup` untuk membuat akun Superadmin pertama.
- Role `superadmin`, `is_active = true`, dan `employee_id = null` ditentukan secara tegas di server-side.
- Endpoint `/setup` otomatis dikunci server-side begitu akun Superadmin aktif pertama dibuat.

## Non-Goals MVP

Jangan implementasikan pada MVP:
- payroll lengkap;
- fingerprint hardware;
- integrasi mesin absensi;
- face recognition AI;
- liveness detection;
- anti-spoof tingkat enterprise;
- mobile app native Android/iOS;
- multi-company;
- microservices;
- real-time websocket infrastructure.

Semua dapat menjadi roadmap setelah sistem inti stabil.
