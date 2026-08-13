# SPRINT 18.5.5 — Fresh Install & First-Run Superadmin Setup

## Tujuan

Mempersiapkan aplikasi agar dapat digunakan dari kondisi database benar-benar kosong seperti instalasi baru.

Sprint ini juga membuat mekanisme **First-Run Setup** untuk membuat Superadmin pertama secara aman tanpa default credential.

Urutan sprint:

```text
SPRINT_18.5
User Role Management
        ↓
SPRINT_18.5.5
Fresh Install & First-Run Superadmin Setup
        ↓
SPRINT_18.6
Forgot Password & Email Reset
        ↓
SPRINT_19
Shared Hosting Deployment
```

JANGAN mengerjakan SPRINT_18.6 atau SPRINT_19 pada sprint ini.

---

# 1. KONDISI KHUSUS SPRINT INI

Untuk sprint ini developer SECARA EKSPLISIT meminta reset database development saat ini.

Semua data development/manual/test yang ada saat ini boleh dihapus.

Tujuannya adalah:

```text
menganggap aplikasi baru pertama kali di-install
```

Karena itu, HANYA UNTUK SPRINT INI pada database development lokal yang benar-benar dimaksudkan untuk dibuang, diperbolehkan menjalankan:

```bash
php artisan migrate:fresh
```

JANGAN gunakan:

```bash
php artisan migrate:fresh --seed
```

jika seeder mengandung dummy/test data.

Setelah sprint ini selesai, destructive migration kembali DILARANG untuk database yang sudah berisi data nyata.

---

# 2. BACKUP SAFETY SEBELUM RESET

Walaupun data development memang akan dibuang, sebelum reset:

1. Buat satu Full Backup menggunakan module backup existing.
2. Validasi checksum.
3. Catat Backup UUID/checksum pada completion report.

Backup ini hanya sebagai recovery point jika ternyata ada sesuatu yang perlu diperiksa kembali.

Setelah backup valid:

database development boleh di-reset.

---

# 3. RESET DEVELOPMENT DATABASE

Reset database development menjadi fresh.

Expected setelah fresh migration sebelum first-run setup:

```text
users = 0
employees = 0
job_titles = 0
shifts = 0
attendance_locations = 0
work_schedules = 0
attendance_records = 0
leave_requests = 0
overtime_requests = 0
notifications = 0
audit_logs = 0
backup_records = 0
```

Tabel sistem/configuration yang memang dibuat oleh migration boleh ada.

Jangan isi dummy business data.

---

# 4. NO DEFAULT CREDENTIAL

DILARANG membuat default credential seperti:

```text
admin@admin.com
password
password123
admin123
superadmin123
```

Tidak boleh ada Superadmin dengan password hardcoded.

Tidak boleh ada credential di:

- seeder;
- migration;
- config;
- .env.example;
- README;
- source code;
- automated production setup.

---

# 5. FIRST-RUN SETUP

Implementasikan first-run setup.

Preferred route:

```text
/setup
```

Jika aplikasi belum memiliki Superadmin aktif, akses awal aplikasi harus diarahkan ke setup.

Flow:

```text
Aplikasi dibuka
      ↓
Cek active Superadmin
      ↓
Tidak ada
      ↓
/setup
      ↓
Buat Superadmin pertama
      ↓
Setup selesai
      ↓
/setup dikunci
      ↓
Redirect Login
```

---

# 6. KONDISI SETUP AVAILABLE

Setup hanya boleh tersedia jika:

```text
active superadmin count = 0
```

atau kondisi equivalent yang aman.

Jangan hanya menggunakan:

```text
users count = 0
```

jika itu dapat menyebabkan aplikasi terkunci ketika ada user non-superadmin tetapi Superadmin belum dibuat.

Source of truth:

```text
active Superadmin exists?
```

---

# 7. SERVER-SIDE PROTECTION

Protection WAJIB dilakukan server-side.

Jika sudah ada active Superadmin:

request ke:

```text
/setup
```

harus ditolak atau diarahkan ke login/dashboard.

Contoh response:

```text
302 → /login
```

atau:

```text
403
```

sesuai architecture existing.

Tidak boleh hanya menyembunyikan menu/link.

---

# 8. SETUP FORM

Form minimal:

```text
SETUP SUPERADMIN

Nama
Email
Password
Konfirmasi Password

[Buat Superadmin]
```

Nama boleh mempunyai suggested placeholder:

```text
Super Administrator
```

tetapi tidak wajib.

Email dan password tidak boleh memiliki default value.

---

# 9. FIRST SUPERADMIN DATA

Saat setup berhasil:

buat user:

```text
role = superadmin
is_active = true
```

Email:

- wajib;
- valid;
- unique;
- normalized sesuai rule project.

Password:

- mengikuti password policy existing;
- di-hash;
- tidak pernah disimpan plaintext.

---

# 10. EMPLOYEE RELATION

First Superadmin tidak wajib menjadi Employee.

Preferred:

```text
users.employee_id = null
```

untuk Superadmin sistem.

Jangan otomatis membuat:

- employee;
- jabatan;
- shift;
- schedule;

untuk first Superadmin.

Superadmin adalah application authority, bukan otomatis employee operasional.

---

# 11. SETUP TRANSACTION

Pembuatan first Superadmin harus atomic.

Gunakan DB transaction jika diperlukan.

Jika proses gagal:

jangan meninggalkan partial setup state.

---

# 12. SETUP RACE CONDITION

Cegah dua request setup bersamaan membuat dua first Superadmin.

Saat submit:

server harus melakukan re-check bahwa:

```text
active superadmin count = 0
```

di dalam safe transaction/locking strategy yang sesuai.

Jika Superadmin sudah dibuat oleh request lain:

reject request berikutnya.

---

# 13. SETUP COMPLETE STATE

Preferred source of truth:

```text
active Superadmin exists
```

Jangan bergantung pada boolean yang mudah drift jika tidak diperlukan.

Jika project memang sudah memiliki app setting seperti:

```text
setup_completed
```

boleh digunakan sebagai secondary state.

Tetapi Superadmin existence tetap harus diaudit agar aplikasi dapat recovery dengan aman.

---

# 14. ROOT ROUTING

Saat belum ada active Superadmin:

request ke:

```text
/
```

atau login route boleh diarahkan ke:

```text
/setup
```

Setelah setup selesai:

```text
/ → normal application behavior
/login → login page
/setup → blocked
```

---

# 15. AUTH ROUTES SAAT BELUM SETUP

Saat belum ada Superadmin:

public routes yang aman tetap boleh bekerja jika diperlukan.

Tetapi Admin Portal tidak boleh digunakan sebelum initial setup selesai.

Forgot Password belum dikerjakan pada sprint ini.

---

# 16. NO PUBLIC REGISTRATION

Pastikan aplikasi tidak membuka public registration untuk membuat account biasa.

First-run setup bukan public registration.

Setelah setup complete:

tidak ada endpoint publik yang dapat membuat user tanpa authorization.

---

# 17. FIRST SUPERADMIN AUDIT

Setelah first Superadmin berhasil dibuat, buat audit entry jika audit architecture mendukung bootstrap action.

Contoh:

```text
system.first_superadmin_created
```

Simpan:

- target user ID;
- IP;
- user agent;
- timestamp.

Jangan log password.

Jika Audit Log membutuhkan actor ID:

gunakan nullable/system actor sesuai schema.

---

# 18. SUPERADMIN PROTECTION

Setelah setup selesai, pertahankan rule Sprint 18.5:

- minimal satu active Superadmin harus tetap ada;
- last active Superadmin tidak boleh dinonaktifkan;
- last active Superadmin tidak boleh diturunkan role;
- Owner/Admin tidak boleh membuat Superadmin;
- employee form biasa tidak boleh membuat Superadmin.

---

# 19. ADDITIONAL SUPERADMIN

Jangan tambahkan Superadmin kedua melalui:

```text
Employee Form
Owner Form
Admin Form
```

Jika nanti diperlukan, gunakan:

- secure Superadmin management flow;
atau
- CLI command khusus.

Tetapi tidak perlu membuat fitur tambahan jika belum diperlukan.

---

# 20. CLI FALLBACK

Preferred: sediakan CLI recovery command jika web setup tidak dapat digunakan.

Contoh:

```bash
php artisan selon:create-superadmin
```

atau nama yang konsisten dengan project.

Command hanya boleh:

- membuat Superadmin jika memang tidak ada active Superadmin,
  ATAU
- require explicit secure confirmation jika digunakan untuk recovery.

Untuk MVP, cukup implementasikan safe first-superadmin command.

---

# 21. CLI PASSWORD INPUT

Jika command CLI dibuat:

password input harus hidden.

Jangan:

```text
php artisan create-superadmin --password=password123
```

jika command history dapat merekam credential.

Preferred interactive hidden prompt.

---

# 22. DATABASE SEEDER

Audit:

```text
DatabaseSeeder
```

dan seluruh seeder.

Production/fresh install tidak boleh otomatis membuat:

- dummy employee;
- dummy attendance;
- dummy schedule;
- dummy shift;
- dummy jabatan;
- default admin password.

Jika test seeder diperlukan:

pisahkan jelas dari production/development fresh install workflow.

---

# 23. FACTORY

Factories tetap boleh ada untuk automated tests.

Tetapi jangan otomatis dijalankan saat:

```bash
php artisan migrate
```

atau initial production install.

---

# 24. FIRST-RUN UI

UI setup harus mengikuti branding/design system existing.

Tampilan sederhana, professional, responsive.

Minimal:

- logo/app name dynamic jika branding tersedia;
- heading;
- explanation singkat;
- form;
- password visibility toggle jika design existing mendukung;
- validation errors.

Tidak perlu sidebar/admin navigation.

---

# 25. MOBILE RESPONSIVE

Test viewport:

```text
360
390
430
768
1366
```

Setup page harus usable di mobile.

---

# 26. PASSWORD POLICY

Reuse password rule Sprint 17/18.5.

Jangan membuat password policy baru yang berbeda.

Server-side validation wajib.

---

# 27. EMAIL UNIQUENESS

Email first Superadmin harus unique.

Jika ada inconsistent state:

```text
users exist
tetapi tidak ada Superadmin
```

setup masih boleh membuat Superadmin baru menggunakan email unique.

Jangan delete existing users otomatis.

---

# 28. INCONSISTENT DATABASE RECOVERY

Scenario:

```text
users = 3
active superadmin = 0
```

Expected:

setup/recovery flow masih tersedia.

Tujuannya mencegah aplikasi permanently locked out.

---

# 29. ACTIVE SUPERADMIN CHECK

Jika:

```text
role = superadmin
is_active = false
```

dan tidak ada active Superadmin lain:

anggap aplikasi tidak memiliki active Superadmin.

Recovery/setup path harus tersedia sesuai policy aman.

Jangan menganggap inactive Superadmin cukup untuk mengunci setup.

---

# 30. SECURITY AGAINST ROLE FORGING

Setup endpoint tidak boleh menerima arbitrary role dari request.

Jangan menggunakan:

```php
role = $request->role
```

Role harus ditentukan server-side:

```text
superadmin
```

---

# 31. MASS ASSIGNMENT

Audit mass-assignment protection.

Input setup hanya menerima field yang memang diperlukan:

- name;
- email;
- password;
- password_confirmation.

Jangan menerima:

- role;
- is_active;
- employee_id;
- permissions;
- created_by.

Nilai sensitif ditentukan server-side.

---

# 32. CSRF

Setup form wajib CSRF protected.

Jangan disable CSRF.

---

# 33. RATE LIMITING

Tambahkan rate limiting wajar untuk setup POST.

Walaupun setup hanya tersedia saat belum ada Superadmin, tetap cegah abuse.

Tidak perlu Redis.

Gunakan mechanism compatible shared hosting.

---

# 34. SESSION

Setelah setup selesai:

preferred:

jangan auto-login.

Redirect ke:

```text
/login
```

dengan message:

```text
Superadmin berhasil dibuat. Silakan login.
```

Ini memberikan boundary auth yang jelas.

---

# 35. FIRST LOGIN

Login dengan credential yang baru dibuat harus:

```text
role = superadmin
→ /admin/dashboard
```

Pastikan middleware Sprint 18.5 bekerja.

---

# 36. FRESH INSTALL TEST

Lakukan test nyata:

1. Backup current development DB.
2. Validate checksum.
3. Jalankan intentional fresh reset.
4. Pastikan semua business data kosong.
5. Jalankan migration.
6. Buka aplikasi.
7. Expected redirect `/setup`.
8. Buat first Superadmin.
9. Login.
10. Pastikan Admin Portal dapat digunakan.
11. Pastikan `/setup` tidak dapat diakses lagi.
12. Restart app.
13. Pastikan setup tetap locked.
14. Pastikan Superadmin masih bisa login.

---

# 37. REINSTALL TEST

Untuk automated/test database saja:

test fresh database kedua.

Pastikan application dapat bootstrap tanpa manual SQL edit.

---

# 38. NO DOMAIN HARDCODE

Setup harus domain-agnostic.

DILARANG hardcode:

```text
beauty.selon.my.id
selon.my.id
localhost
127.0.0.1
```

untuk redirect/URL production.

Gunakan:

- named routes;
- route();
- url();
- config;
- current request host.

---

# 39. SHARED HOSTING COMPATIBILITY

First-run setup tidak boleh membutuhkan:

- Redis;
- Supervisor;
- WebSocket;
- PM2;
- Docker;
- runtime Node server.

Harus bekerja pada Laravel + PHP + MariaDB shared hosting biasa.

---

# 40. MIGRATION FRESH INSTALL VALIDATION

Pastikan semua migration dari nol dapat berjalan berurutan.

Jalankan pada test/fresh DB:

```bash
php artisan migrate
```

atau setelah intentional reset:

```bash
php artisan migrate:fresh
```

Tidak boleh ada migration dependency rusak.

Tidak boleh membutuhkan manual database patch.

---

# 41. MIGRATION IDEMPOTENCY

Setelah first migration selesai:

```bash
php artisan migrate
```

ulang harus menghasilkan:

```text
Nothing to migrate
```

dan tidak menghapus data.

---

# 42. PRODUCTION INSTALL POLICY

Update dokumentasi bahwa production installation nanti menggunakan:

```bash
php artisan migrate --force
```

pada database production kosong.

JANGAN gunakan:

```bash
php artisan migrate:fresh
```

pada production.

First Superadmin dibuat melalui:

```text
/setup
```

setelah migration.

---

# 43. RESET RULE SETELAH SPRINT

Setelah SPRINT_18.5.5 selesai:

aturan database kembali:

DILARANG:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

untuk database yang mulai berisi data nyata.

Exception hanya:

- automated test DB;
- disposable local DB;
- explicit fresh-install test yang diminta developer.

---

# 44. DOCUMENTATION UPDATE

Update:

```text
docs/PROJECT_CONTEXT.md
docs/ARCHITECTURE.md
docs/RULES.md
docs/LOCAL_SETUP.md
docs/PRODUCTION_PREFLIGHT.md
```

Tambahkan:

```text
Fresh Install Flow
First-Run Superadmin Setup
No Default Credential
No Production Seeder Dummy Data
```

---

# 45. LOCAL_SETUP.md

Dokumentasikan clean local install:

```text
1. configure .env
2. create empty database
3. php artisan migrate
4. npm install/build if required
5. run application
6. open /setup
7. create first Superadmin
8. login
```

Jangan memasukkan credential contoh yang tampak seperti production default.

---

# 46. PRODUCTION_PREFLIGHT.md

Tambahkan:

```text
[ ] Production database empty/new
[ ] APP_ENV=production
[ ] APP_DEBUG=false
[ ] Database credentials configured
[ ] php artisan migrate --force completed
[ ] No dummy data
[ ] No default Superadmin credential
[ ] /setup available before first Superadmin
[ ] First Superadmin created
[ ] /setup locked afterward
[ ] Login Superadmin successful
```

---

# 47. AUTOMATED TESTS

Tambahkan minimal:

```text
fresh database has no default users

setup page available when no active superadmin exists

root redirects to setup when no active superadmin exists

setup can create first superadmin

first superadmin role is superadmin

first superadmin is active

first superadmin password is hashed

setup does not create employee automatically

setup ignores forged role input

setup ignores forged is_active input

setup rejects invalid email

setup enforces password policy

setup blocked after active superadmin exists

setup post blocked after active superadmin exists

inactive superadmin alone does not permanently lock recovery

existing non-superadmin users do not block first superadmin setup

two setup submissions cannot create uncontrolled multiple first superadmins

first superadmin can login

superadmin reaches admin dashboard

last active superadmin protection remains working

no production seeder creates dummy users
```

---

# 48. REGRESSION TESTS

Pastikan Sprint 18.5 tetap PASS:

- role separation;
- Owner access;
- Admin access;
- Employee access;
- last Superadmin protection;
- role change audit;
- privilege escalation protection.

Jangan merusak:

- Attendance
- Scheduling
- GPS
- Selfie
- Leave
- Overtime
- Reports
- Notifications
- Backup/Restore
- Branding
- PWA

---

# 49. FINAL TEST COMMANDS

Setelah implementation:

```bash
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Kemudian validasi aplikasi secara manual.

---

# 50. FRESH DATABASE FINAL STATE

Setelah manual fresh-install validation selesai, database development boleh berisi HANYA data yang benar-benar dibuat selama setup/manual validation.

Preferred final state sebelum Sprint 18.6:

```text
1 active Superadmin
0 Employee
0 Job Title
0 Shift
0 Schedule
0 Attendance
0 Leave
0 Overtime
```

Tidak ada dummy data lain.

Jika backup/audit system otomatis menghasilkan internal records karena setup/backup:

itu diperbolehkan dan harus dijelaskan.

---

# ACCEPTANCE CRITERIA

SPRINT_18.5.5 hanya PASS jika:

## Fresh Install
- [ ] Backup sebelum reset dibuat dan checksum valid
- [ ] Development DB intentionally reset
- [ ] Migration from zero PASS
- [ ] Tidak ada dummy business data
- [ ] Tidak ada default user/password
- [ ] No production seed data

## First Run
- [ ] /setup tersedia tanpa active Superadmin
- [ ] Root mengarahkan ke setup jika belum initialized
- [ ] First Superadmin dapat dibuat
- [ ] Role ditentukan server-side = superadmin
- [ ] Account active
- [ ] Password hashed
- [ ] Employee record tidak dibuat otomatis
- [ ] Setup transactional/safe

## Security
- [ ] CSRF aktif
- [ ] Rate limiting tersedia
- [ ] Forged role ditolak/diabaikan
- [ ] Forged is_active ditolak/diabaikan
- [ ] Password policy existing digunakan
- [ ] Tidak ada credential plaintext
- [ ] Setup locked setelah Superadmin ada
- [ ] Direct POST setup blocked setelah initialization
- [ ] Last Superadmin protection tetap bekerja

## Recovery
- [ ] Existing non-superadmin users tidak membuat permanent lockout
- [ ] Tidak adanya active Superadmin membuka recovery/setup secara aman
- [ ] Optional CLI fallback tersedia atau recovery web cukup terdokumentasi

## Domain / Hosting
- [ ] Tidak ada hardcoded hostname
- [ ] Shared-hosting compatible
- [ ] Tidak membutuhkan Redis/Supervisor/PM2/WebSocket/Docker

## Validation
- [ ] First Superadmin login berhasil
- [ ] /admin/dashboard dapat dibuka
- [ ] /setup terkunci setelah setup
- [ ] App restart tidak membuka setup lagi
- [ ] Tests PASS
- [ ] Build PASS
- [ ] Cache validation PASS
- [ ] Documentation updated

---

# OUTPUT WAJIB SETELAH SELESAI

Berikan Completion Report:

1. Backup UUID/checksum sebelum reset.
2. Database reset command yang dijalankan.
3. Fresh migration result.
4. Seeder audit result.
5. Data count setelah fresh migration.
6. First-run routing implementation.
7. Setup controller/service/middleware.
8. First Superadmin fields.
9. Password policy/hashing.
10. Server-side setup lock.
11. Race-condition protection.
12. Existing-user/no-superadmin recovery behavior.
13. Last Superadmin protection regression.
14. Domain-agnostic validation.
15. Shared-hosting compatibility.
16. Automated tests result.
17. `npm run build` result.
18. Cache validation result.
19. Final development DB state.
20. Known limitations.

JANGAN mengerjakan SPRINT_18.6.

JANGAN mengerjakan SPRINT_19.

Tunggu instruksi berikutnya.
