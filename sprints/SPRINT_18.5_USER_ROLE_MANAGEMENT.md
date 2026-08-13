# SPRINT 18.5 — User Role Management & Access Control

## Tujuan

Menyempurnakan struktur User, Employee, Jabatan, Role, dan Authorization sebelum deployment production.

Prinsip utama:

```text
JABATAN = posisi pekerjaan
ROLE    = hak akses aplikasi
```

Jabatan TIDAK BOLEH menentukan hak akses. Role TIDAK BOLEH ditentukan dari nama jabatan.

Role final aplikasi:

```text
superadmin
owner
admin
employee
```

Sprint ini dikerjakan setelah SPRINT_18 dan sebelum SPRINT_19.

JANGAN mengerjakan SPRINT_19 sebelum sprint ini selesai dan tervalidasi.

---

# 1. WAJIB BACA SEBELUM CODING

1. Baca `ANTIGRAVITY_MASTER_PROMPT.md`.
2. Baca seluruh dokumentasi mandatory di folder `docs/`.
3. Audit hasil SPRINT_17, SPRINT_17.5, dan SPRINT_18.
4. Audit authentication/authorization yang sudah ada.
5. Pertahankan seluruh security hardening.
6. Jangan rewrite modul stabil tanpa alasan teknis.
7. Jangan menghapus data development existing.
8. Jangan membuat dummy user/employee/role.

# 2. DATABASE SAFETY

DILARANG:

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

Jika migration diperlukan:

```bash
php artisan migrate
```

Automated test wajib menggunakan database test terpisah.

# 3. ROLE ADALAH SUMBER AUTHORIZATION

Role disimpan pada:

```text
users.role
```

Authorization tidak boleh berdasarkan:

```text
employees.job_title_id
job_titles.name
employee_code
nama user
email
```

Semua route/policy/middleware harus menggunakan role aplikasi.

# 4. JABATAN DAN ROLE HARUS TERPISAH

`job_titles` hanya menjelaskan posisi kerja.

Contoh:

```text
Nama      : Ayu Pratama
Jabatan   : Admin Toko
Role      : employee
```

Ayu tetap hanya memiliki Employee Portal.

Contoh:

```text
Nama      : Siti
Jabatan   : Supervisor
Role      : admin
```

Siti memiliki Admin Portal karena role `admin`, bukan karena jabatan.

Nama jabatan `Admin`, `Owner`, atau `Superadmin` tidak boleh otomatis memberikan role tersebut.

# 5. AUDIT HARDCODE ROLE DARI JABATAN

Cari logic seperti:

```php
$employee->jobTitle->name === 'Admin'
$employee->jobTitle->name === 'Owner'
```

Jika dipakai untuk authorization, perbaiki.

Gunakan:

```php
auth()->user()->role
```

atau Policy/Gate/Middleware terpusat.

# 6. ROLE HIERARCHY

Gunakan hierarchy konseptual:

```text
SUPERADMIN
    ↓
OWNER
    ↓
ADMIN
    ↓
EMPLOYEE
```

Gunakan policy yang eksplisit, readable, dan testable.

# 7. SUPERADMIN

Superadmin adalah role tertinggi dan dapat:

- mengakses seluruh Admin Portal;
- mengelola role user;
- assign Owner/Admin/Employee;
- mengakses Backup & Restore;
- melakukan Restore;
- mengakses Branding;
- mengakses Audit Log;
- mengakses Security Settings.

Superadmin tidak boleh dibuat otomatis dari jabatan atau employee form biasa.

# 8. OWNER

Owner dapat mengakses operasional utama:

- Dashboard
- Karyawan
- Jabatan
- Shift
- Jadwal
- Absensi
- Izin/Cuti
- Lembur
- Laporan
- Notifications
- Settings operasional
- Branding
- Backup create/download

Owner dapat mengubah role antara:

```text
employee ↔ admin
```

Owner TIDAK BOLEH:

- membuat Superadmin;
- menaikkan dirinya ke Superadmin;
- assign Owner lain pada MVP;
- menurunkan atau menonaktifkan Superadmin.

Hanya Superadmin yang dapat assign `owner`.

# 9. ADMIN

Admin dapat mengakses operasional harian sesuai policy existing.

Admin TIDAK BOLEH:

- mengubah role user;
- membuat Owner;
- membuat Superadmin;
- Restore backup;
- mengubah security setting sensitif;
- melakukan privilege escalation terhadap dirinya sendiri.

# 10. EMPLOYEE

Employee hanya memiliki Employee Portal:

```text
/app/*
```

Employee tidak boleh mengakses `/admin/*` walaupun mengetik URL manual.

# 11. DEFAULT ROLE EMPLOYEE

Saat karyawan baru dibuat, default role wajib:

```text
employee
```

Jangan infer role dari jabatan.

Contoh:

```text
Nama    : Siti
Jabatan : Admin Toko
```

Role tetap:

```text
employee
```

sampai actor berwenang mengubah Role Aplikasi secara eksplisit.

# 12. EMPLOYEE FORM — AKUN & AKSES

Pada halaman create/edit employee tambahkan section:

```text
AKUN & AKSES APLIKASI
```

Minimal:

```text
Email Login
Status Akun
Role Aplikasi
```

Tambahkan helper:

```text
Role menentukan hak akses aplikasi dan berbeda dari Jabatan Karyawan.
```

# 13. ROLE LABEL UI

Gunakan label UI:

```text
superadmin → Superadmin
owner      → Owner
admin      → Admin
employee   → Karyawan
```

Database tetap menyimpan canonical role string.

# 14. ROLE SELECTOR — SUPERADMIN

Saat Superadmin mengedit user biasa, pilihan role:

```text
Karyawan
Admin
Owner
```

Jangan tampilkan `Superadmin` sebagai pilihan biasa.

Jika multi-superadmin diperlukan, gunakan flow khusus dengan re-authentication dan audit.

# 15. ROLE SELECTOR — OWNER

Saat Owner mengedit employee/account, pilihan:

```text
Karyawan
Admin
```

Jangan tampilkan:

```text
Owner
Superadmin
```

Server juga harus reject forged request untuk role tersebut.

# 16. ROLE SELECTOR — ADMIN

Admin tidak boleh mendapatkan editable role selector.

Role boleh ditampilkan read-only untuk konteks.

# 17. PRIVILEGE ESCALATION PROTECTION

Server harus reject forged requests seperti:

```text
role=superadmin
role=owner
```

jika actor tidak berwenang.

Frontend dropdown bukan security control.

# 18. SELF ROLE CHANGE

User tidak boleh menaikkan role dirinya sendiri di luar permission.

Contoh:

Admin mengirim request terhadap dirinya:

```text
role=owner
```

harus ditolak.

Owner juga tidak dapat menjadi Superadmin melalui request manual.

# 19. LAST ACTIVE SUPERADMIN PROTECTION

Jika hanya ada satu Superadmin aktif, user tersebut tidak boleh:

- dinonaktifkan;
- dihapus;
- diturunkan role;

jika akibatnya:

```text
active superadmin count = 0
```

Tampilkan pesan:

```text
Minimal satu Superadmin aktif harus tetap tersedia.
```

# 20. INACTIVE ACCOUNT

Role tidak mengalahkan status account.

Jika:

```text
is_active = false
```

user tidak boleh login atau mempertahankan akses walaupun role tinggi.

# 21. USER ↔ EMPLOYEE RELATION

Role `employee` wajib memiliki `users.employee_id` yang valid.

Admin atau Owner yang juga merupakan karyawan boleh tetap memiliki employee relation.

Contoh:

```text
users.role = admin
users.employee_id = 3
```

Jangan menghapus employee relation hanya karena role dinaikkan.

# 22. ADMIN/OWNER YANG JUGA KARYAWAN

Jika Admin/Owner memiliki employee record, mereka boleh tetap mempunyai:

- jadwal;
- attendance;
- leave;
- overtime;

jika business operation membutuhkannya.

Hak Admin Portal tetap berasal dari `users.role`.

# 23. LOGIN REDIRECT

Setelah login:

```text
superadmin → /admin/dashboard
owner      → /admin/dashboard
admin      → /admin/dashboard
employee   → /app/dashboard
```

# 24. APP KARYAWAN BUTTON

Audit tombol `App Karyawan` untuk Owner/Admin.

Jangan jadikan tombol ini impersonation arbitrary employee.

Jika Owner/Admin memiliki employee relation sendiri, boleh membuka portal miliknya.

Jika hanya preview UI, jangan expose data employee lain.

# 25. USER MANAGEMENT UI

Preferred structure:

```text
Karyawan → Detail/Edit → Akun & Akses
```

Jika dibutuhkan halaman global:

```text
Pengaturan → User & Akses
```

buat hanya untuk Superadmin agar pengaturan role sensitif tidak membingungkan.

# 26. DISPLAY JABATAN VS ROLE

Pada detail employee tampilkan jelas:

```text
Jabatan
Beautician

Role Aplikasi
Karyawan
```

Gunakan visual/badge berbeda agar pengguna memahami keduanya bukan hal yang sama.

# 27. JOB TITLE UI

Menu `Jabatan` tetap hanya CRUD posisi pekerjaan.

JANGAN menambahkan permission/role logic ke `job_titles`.

# 28. ROLE CHANGE AUDIT LOG

Setiap perubahan role wajib membuat audit log.

Action:

```text
user.role_changed
```

Simpan:

- actor;
- target user;
- employee relation jika ada;
- before role;
- after role;
- IP;
- user agent;
- timestamp.

Jangan menyimpan password.

# 29. SESSION INVALIDATION SETELAH ROLE CHANGE

Jika role target berubah, session target harus diinvalidate/revoke jika memungkinkan.

Contoh:

```text
admin → employee
```

user tidak boleh tetap mengakses Admin Portal melalui session lama.

Preferred: paksa login ulang setelah perubahan role.

# 30. ROLE CHANGE NOTIFICATION

Jika Notification module mendukung, kirim notification ke target:

```text
Hak akses akun Anda diubah dari Karyawan menjadi Admin.
```

Optional tetapi preferred.

# 31. POLICY / MIDDLEWARE AUDIT

Audit seluruh role middleware/policy agar mengenal:

```text
superadmin
owner
admin
employee
```

Jangan membuat Superadmin gagal mengakses route Owner/Admin karena middleware lama terlalu sempit.

# 32. CENTRALIZED SUPERADMIN ACCESS

Jika architecture mendukung, gunakan Gate/Policy centralized untuk Superadmin.

Jangan menyebarkan puluhan kondisi:

```php
if ($role === 'superadmin')
```

ke banyak controller.

# 33. SENSITIVE ACCESS MATRIX

Gunakan policy final:

## Restore Backup

```text
superadmin only
```

## Backup Create / Download / Schedule

```text
superadmin + owner
```

## Application Branding

```text
superadmin + owner
```

## Audit Logs

```text
superadmin + owner
```

## Role Management

```text
superadmin: owner/admin/employee
owner: admin/employee
admin: none
employee: none
```

Jika implementasi SPRINT 17.5 sebelumnya mengizinkan Owner melakukan Restore, ubah menjadi Superadmin-only dan dokumentasikan perubahan policy ini.

# 34. DATABASE ROLE FIELD

Audit `users.role`.

Pastikan menerima:

```text
superadmin
owner
admin
employee
```

Jika menggunakan ENUM dan `superadmin` belum tersedia, buat migration NON-DESTRUCTIVE.

Jangan recreate users table.

# 35. CENTRAL ROLE ENUM / CONSTANTS

Preferred gunakan PHP enum atau centralized constants:

```text
UserRole::SUPERADMIN
UserRole::OWNER
UserRole::ADMIN
UserRole::EMPLOYEE
```

Hindari typo string role tersebar di seluruh aplikasi.

# 36. SUPERADMIN CREATION COMMAND

Audit command existing untuk membuat Superadmin.

Pastikan:

- tidak ada password default;
- email unique;
- password prompt aman;
- role `superadmin` explicit;
- `is_active=true`;
- password tidak muncul di output/log.

Jika belum ada command aman, buat:

```bash
php artisan selon:create-superadmin
```

atau nama konsisten dengan project.

# 37. FIRST SUPERADMIN

Production wajib memiliki minimal satu Superadmin sebelum go-live.

Jangan seed akun Superadmin default.

# 38. OWNER CREATION

Superadmin dapat membuat/assign Owner melalui User Access management.

Owner tidak wajib memiliki jabatan `Owner`.

Jika Owner memang employee toko, employee relation dan jabatan dapat diatur terpisah.

# 39. SECURITY TEST — JABATAN

Tambahkan tests:

```text
employee with job title Admin remains employee role
employee with job title Owner remains employee role
job title name does not grant admin access
job title name does not grant owner access
```

# 40. SECURITY TEST — SUPERADMIN

Tambahkan tests:

```text
superadmin can access admin dashboard
superadmin can manage owner
superadmin can manage admin
superadmin can manage employee
owner cannot assign superadmin
admin cannot assign role
employee cannot assign role
last active superadmin cannot be demoted
last active superadmin cannot be deactivated
```

# 41. SECURITY TEST — ROLE CHANGE

Tambahkan tests:

```text
superadmin can promote employee to admin
superadmin can promote admin to owner
owner can promote employee to admin
owner cannot promote admin to owner
admin cannot promote employee
user cannot promote self beyond permission
forged superadmin role request rejected
role change creates audit log
role change invalidates target session
```

# 42. ACCESS TEST

Tambahkan:

```text
superadmin can access owner/admin routes
owner can access allowed admin routes
admin can access operational routes
employee cannot access admin routes
```

# 43. SENSITIVE FEATURE TEST

Tambahkan:

```text
superadmin can restore backup
owner cannot restore backup
admin cannot restore backup
employee cannot restore backup

superadmin can create backup
owner can create backup
admin cannot create backup
employee cannot create backup

superadmin can update branding
owner can update branding
admin cannot update branding
employee cannot update branding

superadmin can view audit logs
owner can view audit logs
admin cannot view sensitive audit logs
employee cannot view audit logs
```

# 44. MANUAL VALIDATION

Gunakan existing data tanpa reset database.

1. Login Superadmin.
2. Buka employee Ayu.
3. Pastikan Jabatan dan Role Aplikasi tampil terpisah.
4. Ubah jabatan Ayu menjadi `Admin Toko`.
5. Pastikan role tetap `employee`.
6. Login Ayu dan pastikan tetap hanya Employee Portal.
7. Login Superadmin.
8. Ubah role Ayu `employee → admin`.
9. Login ulang Ayu dan pastikan Admin Portal tersedia.
10. Ubah kembali `admin → employee`.
11. Pastikan session lama kehilangan akses Admin.
12. Login Owner.
13. Pastikan Owner dapat assign `employee ↔ admin`.
14. Pastikan Owner tidak dapat assign `owner`/`superadmin`.
15. Login Admin.
16. Pastikan Admin tidak dapat mengubah role.
17. Test forged role request.
18. Pastikan Audit Log tercatat.

# 45. SUPERADMIN SAFETY TEST

Jika hanya ada satu active Superadmin, coba deactivate/demote melalui isolated test.

Expected:

```text
Operation rejected.
```

Jangan kehilangan akses Superadmin pada development utama.

# 46. REGRESSION

Pastikan tetap bekerja:

- Authentication
- Employee Management
- Job Titles
- Shift
- Scheduling
- Attendance
- GPS
- Selfie
- Check-in/out
- Leave
- Overtime
- Reports
- Notifications
- Audit Logs
- Backup/Restore
- Branding
- PWA

# 47. DOCUMENTATION UPDATE

Update:

```text
docs/PROJECT_CONTEXT.md
docs/ARCHITECTURE.md
docs/RULES.md
```

Tambahkan rule permanen:

```text
Job Title != Application Role
```

serta hierarchy:

```text
superadmin
owner
admin
employee
```

# 48. RULES.md WAJIB

Tambahkan:

## Job Title Is Not Authorization

Nama jabatan tidak boleh digunakan untuk menentukan authorization.

## Least Privilege Role Assignment

Role default user employee adalah `employee`.

Privilege escalation hanya dapat dilakukan actor yang memiliki permission.

## Superadmin Protection

Minimal satu active Superadmin harus tetap tersedia.

# 49. DATABASE PERSISTENCE VALIDATION

Restart aplikasi setelah perubahan.

Pastikan seluruh data existing tetap utuh.

# 50. FINAL BACKUP

Setelah SPRINT 18.5 PASS:

buat satu Full Backup baru menggunakan Backup module.

Validate checksum.

Backup ini menjadi recovery point final sebelum SPRINT_19.

# 51. FINAL TEST

Jalankan:

```bash
php artisan migrate
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika perlu melanjutkan local development setelah cache validation:

```bash
php artisan optimize:clear
```

# ACCEPTANCE CRITERIA

SPRINT 18.5 hanya PASS jika:

## Separation

- [ ] Job Title dan Role benar-benar terpisah
- [ ] Jabatan Admin tidak memberikan Admin access
- [ ] Jabatan Owner tidak memberikan Owner access
- [ ] Authorization hanya berbasis Role/Policy

## Roles

- [ ] `superadmin` tersedia
- [ ] `owner` tersedia
- [ ] `admin` tersedia
- [ ] `employee` tersedia
- [ ] New employee default = employee

## Superadmin

- [ ] Superadmin dapat manage role
- [ ] Superadmin dapat assign Owner
- [ ] Owner tidak dapat assign Superadmin
- [ ] Admin tidak dapat assign role
- [ ] Employee tidak dapat assign role
- [ ] Last active Superadmin protection bekerja

## Security

- [ ] Forged role request ditolak
- [ ] Self privilege escalation ditolak
- [ ] Role change audit tercatat
- [ ] Target session invalidated setelah role change
- [ ] Inactive account tetap blocked

## UI

- [ ] Employee detail menampilkan Jabatan dan Role terpisah
- [ ] Role selector sesuai permission actor
- [ ] Helper text menjelaskan Jabatan != Role
- [ ] Admin tidak melihat editable role selector
- [ ] UI responsive

## Sensitive Access

- [ ] Backup create sesuai policy
- [ ] Restore Superadmin-only
- [ ] Branding Superadmin/Owner
- [ ] Audit Log Superadmin/Owner
- [ ] Employee tidak dapat mengakses resource Admin

## Regression

- [ ] Attendance tetap bekerja
- [ ] Scheduling tetap bekerja
- [ ] GPS/Selfie tetap bekerja
- [ ] Leave/Overtime tetap bekerja
- [ ] Reports tetap bekerja
- [ ] Notifications tetap bekerja
- [ ] Backup/Restore tetap bekerja
- [ ] Branding/PWA tetap bekerja
- [ ] Existing data tetap utuh
- [ ] Full tests PASS
- [ ] Build PASS
- [ ] Cache validation PASS
- [ ] Final Full Backup dibuat dan checksum VALID

# OUTPUT WAJIB SETELAH SELESAI

Berikan Completion Report:

1. Existing role structure sebelum perubahan.
2. Job Title authorization issue yang ditemukan.
3. Final role hierarchy.
4. Middleware/Policy/Gate yang diperbaiki.
5. Employee/User Management UI changes.
6. Superadmin protections.
7. Owner permissions.
8. Admin permissions.
9. Session invalidation behavior.
10. Audit log role changes.
11. Sensitive feature access matrix.
12. Database migration yang dibuat.
13. Existing user role migration result.
14. Automated test result.
15. `npm run build` result.
16. Cache validation result.
17. Existing data persistence result.
18. Final Full Backup UUID/checksum validation.
19. Known limitations.

JANGAN mengerjakan SPRINT_19.

Tunggu instruksi deployment berikutnya.
