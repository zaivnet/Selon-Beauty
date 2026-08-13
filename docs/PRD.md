# PRODUCT REQUIREMENTS DOCUMENT

## 1. Product Overview

SELON BEAUTY Attendance adalah aplikasi web/PWA untuk mengatur jadwal kerja dan mencatat kehadiran karyawan berbasis lokasi dan selfie.

## 2. Tujuan

### Owner
- Mengetahui kondisi kehadiran hari ini dalam beberapa detik.
- Membuat jadwal tanpa spreadsheet manual.
- Memiliki bukti check-in/check-out.
- Memproses pengajuan karyawan.
- Menghasilkan laporan bulanan yang mudah dibaca.

### Employee
- Melihat shift hari ini.
- Check-in/check-out dari HP.
- Mendapat feedback jelas apakah absensi berhasil.
- Melihat riwayat dan status pengajuan.

## 3. KPI Produk

- 100% tindakan check-in valid menghasilkan record yang dapat ditelusuri.
- Tidak ada duplikasi check-in untuk employee + tanggal kerja yang sama kecuali dikoreksi Admin.
- Waktu halaman utama mobile tetap ringan.
- Semua perubahan manual pada absensi memiliki audit trail.
- Tidak ada data demo setelah deployment production.

## 4. Functional Requirements

### FR-01 Authentication
- Login email/nomor HP + password.
- Logout.
- Forgot/reset password bila email tersedia.
- Rate limiting login.
- Session regeneration setelah login.
- Role-based authorization.

### FR-02 Employee Management
Owner dapat:
- tambah;
- edit;
- aktif/nonaktif;
- lihat detail;
- reset kredensial;
- menetapkan jabatan;
- menetapkan tanggal masuk.

Employee code harus unik.

### FR-03 Attendance Location
Owner dapat menentukan:
- nama lokasi;
- latitude;
- longitude;
- radius meter;
- maksimum GPS accuracy yang diterima;
- status aktif.

Versi awal cukup mendukung satu lokasi aktif, tetapi schema harus siap multi-location.

### FR-04 Shift
Shift mempunyai:
- nama;
- jam mulai;
- jam selesai;
- toleransi keterlambatan;
- jendela check-in;
- jendela check-out;
- durasi istirahat opsional;
- flag lintas tengah malam.

### FR-05 Scheduling
Owner dapat:
- menetapkan shift per employee per tanggal;
- menetapkan OFF;
- melihat kalender mingguan/bulanan;
- menyalin jadwal minggu sebelumnya;
- mendeteksi bentrok jadwal.

Jangan otomatis membuat schedule dummy.

### FR-06 Check-in
Syarat:
1. employee aktif;
2. memiliki jadwal kerja hari tersebut;
3. belum check-in;
4. browser memberi izin lokasi;
5. GPS accuracy <= batas yang ditentukan;
6. jarak <= radius lokasi;
7. selfie berhasil diambil;
8. request lolos validasi server.

Simpan:
- server timestamp;
- GPS;
- accuracy;
- calculated distance;
- selfie;
- IP;
- user agent.

Jangan percaya jam dari client sebagai timestamp utama.

### FR-07 Check-out
Syarat:
- sudah check-in;
- belum check-out;
- validasi lokasi sesuai kebijakan;
- selfie check-out;
- server timestamp.

Hitung:
- worked minutes;
- early leave minutes;
- overtime candidate bila relevan.

### FR-08 Attendance Status
Status dasar:
- present;
- late;
- absent;
- permission;
- sick;
- leave;
- off;
- holiday.

Flag tambahan:
- early_leave;
- manually_adjusted.

Jangan mencampur status utama dan flag secara ambigu.

### FR-09 Leave Request
Employee dapat mengajukan:
- permission;
- sick;
- leave.

Data:
- start_date;
- end_date;
- reason;
- attachment opsional;
- status pending/approved/rejected;
- reviewer;
- reviewer note;
- reviewed_at.

### FR-10 Attendance Correction
Owner/Admin dapat:
- memperbaiki check-in;
- memperbaiki check-out;
- mengubah status dengan alasan.

Setiap perubahan wajib:
- reason;
- actor;
- before snapshot;
- after snapshot;
- timestamp.

### FR-11 Overtime
MVP:
- owner dapat mencatat/menyetujui lembur;
- employee dapat melihat hasil.
Payroll otomatis tidak termasuk MVP.

### FR-12 Reports
Filter:
- range tanggal;
- employee;
- status.

Ringkasan:
- hadir;
- terlambat;
- absen;
- izin;
- sakit;
- cuti;
- total late minutes;
- total worked minutes;
- early leave.

Export:
- CSV/Excel-friendly;
- PDF bila dependency kompatibel shared hosting;
- print view.

### FR-13 Dashboard Owner
Tampilkan:
- total employee aktif;
- hadir hari ini;
- terlambat;
- belum check-in;
- izin/sakit/cuti;
- daftar kehadiran hari ini;
- pengajuan pending;
- tren sederhana.

### FR-14 Dashboard Employee
Tampilkan:
- greeting;
- tanggal;
- shift hari ini;
- status absensi;
- CTA Check In / Check Out;
- status lokasi;
- ringkasan jam;
- pengajuan terbaru.

### FR-15 PWA
- manifest;
- icon set;
- standalone display;
- installable bila browser memenuhi syarat;
- service worker hanya untuk static shell dan safe caching;
- jangan cache response autentikasi atau data sensitif secara agresif.

### FR-16 Notifications
MVP:
- in-app notification.
Optional:
- email jika konfigurasi mail tersedia.

Tidak wajib push notification pada MVP.

## 5. Non-Functional Requirements

### Security
- HTTPS mandatory untuk production.
- CSRF protection.
- server-side validation.
- authorization policy/gate.
- secure file upload.
- no public storage listing.
- password hashing.
- session security.
- rate limiting.
- audit logs.

### Performance
- pagination untuk list.
- eager loading untuk menghindari N+1.
- query index sesuai schema.
- image selfie dikompresi secara aman.
- dashboard query tidak melakukan full-table scans yang tidak perlu.

### Accessibility
- kontras yang cukup;
- button minimum touch target;
- label form;
- state error/success jelas;
- jangan mengandalkan warna saja.

## 6. Out of Scope

- payroll;
- recruitment;
- performance review;
- HRIS enterprise;
- biometrics AI;
- chat;
- marketplace;
- accounting.

## 7. Definition of Done

Sebuah fitur dianggap selesai jika:
- UI selesai;
- backend selesai;
- database selesai;
- authorization selesai;
- validation selesai;
- test utama lolos;
- empty state tersedia;
- error state tersedia;
- responsive;
- tidak ada dummy data;
- tidak ada console error;
- tidak ada tombol palsu;
- dokumentasi sprint diperbarui.
