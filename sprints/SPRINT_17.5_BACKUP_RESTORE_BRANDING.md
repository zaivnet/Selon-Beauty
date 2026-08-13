# SPRINT 17.5 — Backup, Restore, Scheduled Backup & Application Branding

## Tujuan

Menambahkan fitur operasional penting sebelum Performance Cleanup dan Production Deployment:

- Backup database manual.
- Backup file penting aplikasi.
- Riwayat backup.
- Download backup.
- Restore backup secara aman.
- Pre-restore safety backup.
- Scheduled backup.
- Retention policy.
- Application branding yang dapat dikustomisasi Owner.
- Nama aplikasi, logo, icon, PWA metadata, dan warna brand tidak lagi hardcoded.

Sprint ini berada DI ANTARA:

- SPRINT_17 — Security & Audit Hardening
- SPRINT_18 — Performance, Cleanup & Stability

JANGAN mengerjakan SPRINT_18 sebelum sprint ini selesai dan tervalidasi.

# 1. WAJIB BACA SEBELUM CODING

Sebelum coding:

1. Baca `ANTIGRAVITY_MASTER_PROMPT.md`.
2. Baca seluruh dokumen mandatory pada folder `docs/`.
3. Audit hasil SPRINT_17.
4. Pertahankan seluruh security hardening.
5. Jangan rewrite business logic yang sudah stabil.
6. Jangan menghapus data development existing.
7. Jangan membuat dummy backup, dummy branding, atau fake restore result.

# 2. DATABASE PERSISTENCE — KRITIS

Development database sudah berisi data manual penting.

DILARANG:

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

Jika migration diperlukan gunakan:

```bash
php artisan migrate
```

Data berikut HARUS tetap utuh:

- Users
- Employees
- Job Titles
- Attendance Locations
- Shifts
- Work Schedules
- Attendance Records
- Selfies
- Leave Requests
- Leave Attachments
- Overtime Requests
- Notifications
- Audit Logs
- Settings

Automated test wajib menggunakan testing database terpisah.

# 3. MENU ADMIN

Tambahkan / rapikan menu:

```text
Pengaturan
├── Profil Aplikasi
├── Pengaturan Absensi
└── Backup & Restore
```

Tidak perlu menambah menu utama sidebar baru jika `Pengaturan` sudah tersedia.

Gunakan sub-navigation/tab/card yang konsisten dengan UI SPRINT_16.

# 4. APPLICATION BRANDING SETTINGS

Tambahkan halaman:

```text
/admin/settings/branding
```

atau route equivalent yang konsisten.

Owner dapat mengatur:

- Nama Aplikasi
- Nama Singkat
- Nama Perusahaan / Brand
- Tagline opsional
- Logo utama
- Icon aplikasi
- Favicon
- Warna Primary
- Warna Accent
- Warna Theme PWA

Default dapat berasal dari konfigurasi existing SELON BEAUTY.

Jangan membuat data dummy.

# 5. BRANDING DATABASE

Gunakan `app_settings` yang sudah ada jika sesuai.

Contoh keys:

```text
app_name
app_short_name
company_name
app_tagline
app_logo_path
app_icon_path
app_favicon_path
brand_primary
brand_accent
pwa_theme_color
```

Jangan membuat tabel baru jika `app_settings` sudah cukup.

Jika struktur settings perlu diperbaiki, gunakan migration non-destructive.

# 6. JANGAN HARDCODE BRAND NAME

Audit aplikasi untuk string hardcoded seperti:

```text
SELON BEAUTY
SELON BEAUTY Attendance
Employee Portal
Admin Portal
```

Brand utama harus membaca dari settings/config helper.

Jangan melakukan search-replace membabi buta.

Pastikan:

- fallback tersedia jika setting belum diisi;
- test tetap stabil;
- business text yang memang bukan branding jangan ikut berubah.

# 7. TEMPAT BRANDING HARUS BERUBAH

Nama/logo/icon yang dikustomisasi harus terefleksi pada:

- Login page
- Browser title
- Admin sidebar
- Admin header
- Employee mobile header
- Employee dashboard
- PWA manifest
- Install App UI
- Offline PWA page
- Favicon
- Print report header
- Notification title jika saat ini menggunakan app name

Jangan mengubah historical data hanya karena branding berubah.

# 8. LOGO UPLOAD

Owner dapat upload logo.

Validasi server-side:

Allowed MIME minimal:

```text
image/png
image/jpeg
image/webp
```

Jika SVG ingin didukung:

jangan izinkan SVG arbitrary tanpa sanitization.

Preferred untuk MVP:

JANGAN dukung SVG upload.

Rules:

- max file size reasonable;
- verify actual image;
- random filename;
- jangan gunakan nama file user sebagai path;
- delete file lama hanya setelah file baru berhasil disimpan;
- jangan menghapus shared/default asset yang masih diperlukan.

# 9. APP ICON

Icon aplikasi digunakan untuk PWA.

Owner dapat upload master icon.

System harus menghasilkan atau menerima ukuran PWA yang diperlukan.

Minimal:

```text
192x192
512x512
```

Jika implementasi resize server-side sudah tersedia dan ringan:

generate ukuran icon secara otomatis.

Jika tidak:

validasi master image square dengan resolusi minimum yang cukup.

Jangan bergantung pada service eksternal.

# 10. FAVICON

Branding settings harus dapat menentukan favicon.

Pastikan path favicon:

- valid;
- tidak 404;
- tidak menggunakan placeholder Laravel/Vite.

# 11. BRAND COLORS

Owner dapat mengatur:

- Primary Color
- Accent Color
- PWA Theme Color

Gunakan HTML color input + text hex validation.

Contoh valid:

```text
#F50057
#111827
```

Server validate format:

```regex
^#[0-9A-Fa-f]{6}$
```

Jangan menerima arbitrary CSS.

# 12. BRANDING CSS STRATEGY

Jangan compile Tailwind ulang setiap Owner mengganti warna.

Gunakan CSS variables untuk runtime branding.

Contoh konsep:

```css
:root {
    --brand-primary: ...;
    --brand-accent: ...;
}
```

Jangan membuat inline style arbitrary dari input user tanpa validation.

# 13. PWA MANIFEST BRANDING

SPRINT_15 sudah memiliki PWA.

Update manifest agar menggunakan setting:

- name
- short_name
- theme_color
- icons

Jika manifest sekarang static file dan tidak dapat membaca DB:

ubah menjadi dynamic manifest endpoint jika architecture memungkinkan.

Pastikan Content-Type benar.

Jangan merusak PWA installability.

# 14. SERVICE WORKER ICON CACHE

Jika icon/logo berubah:

pastikan service worker/cache strategy tidak membuat icon lama bertahan selamanya.

Gunakan cache version/update strategy yang aman.

Jangan cache private data.

# 15. BACKUP & RESTORE PAGE

Tambahkan:

```text
/admin/settings/backups
```

Owner-only.

Halaman memiliki section:

1. Backup Sekarang
2. Riwayat Backup
3. Restore
4. Backup Terjadwal
5. Retention / Penyimpanan

# 16. BACKUP TYPES

Support minimal:

## Database Backup

Berisi data database aplikasi.

## Full Application Data Backup

Berisi:

- Database
- Attendance selfies
- Leave attachments
- Branding uploaded assets

Tidak perlu memasukkan:

- vendor
- node_modules
- logs
- cache
- `.env`
- APP_KEY
- DB password
- secrets

Backup adalah backup DATA, bukan seluruh source code project.

# 17. BACKUP FORMAT

Buat format backup yang terstruktur dan versioned.

Preferred:

```text
ZIP
├── backup-manifest.json
├── database/
│   └── ...
├── files/
│   ├── attendance/
│   ├── leave/
│   └── branding/
└── checksums.json
```

Manifest minimal:

```text
backup_format_version
app_version
created_at
created_by
database_driver
schema/migration fingerprint
backup_type
included_components
record_counts
file_counts
```

JANGAN menyimpan credential database dalam manifest.

# 18. BACKUP CHECKSUM

Setiap backup harus memiliki integrity validation.

Gunakan:

```text
SHA-256
```

Saat restore checksum harus diverifikasi sebelum perubahan database dilakukan.

# 19. BACKUP STORAGE

Backup HARUS disimpan PRIVATE.

Contoh:

```text
storage/app/private/backups/
```

Jangan simpan backup di:

```text
public/
public_html/
storage/app/public/
```

Backup hanya dapat didownload melalui authorized controller.

# 20. BACKUP FILE NAME

Gunakan nama aman.

Contoh:

```text
selon-backup-full-2026-08-12-134500-AB12CD.zip
```

Jangan gunakan user input langsung sebagai filename.

# 21. BACKUP HISTORY

Tampilkan riwayat:

- Created At
- Type
- Size
- Created By
- Status
- Checksum status
- Components
- Action

Actions:

- Download
- Validate
- Restore
- Delete

Jangan memberikan direct public file URL.

# 22. BACKUP RECORDS

Jika diperlukan buat table:

```text
backup_records
```

Minimal:

```text
id
backup_uuid
type
file_path
file_size
checksum
status
created_by
created_at
metadata JSON
```

Status:

```text
creating
completed
failed
deleted
```

# 23. BACKUP ENGINE — SHARED HOSTING COMPATIBLE

Target production adalah shared hosting.

JANGAN menjadikan `mysqldump` requirement absolut.

Implement backup engine dengan capability detection.

Preferred strategy:

### Strategy A — mysqldump

Gunakan jika binary tersedia dan execution diizinkan.

### Strategy B — Application-Level Logical Backup

Fallback jika mysqldump tidak tersedia.

Fallback harus dapat mengekspor data tabel aplikasi secara terstruktur, misalnya:

```text
JSON / NDJSON / structured SQL generated safely
```

Restore fallback harus deterministic.

Jangan menyatakan backup sukses jika sebenarnya hanya file kosong.

# 24. CAPABILITY DETECTION

Backup page boleh menampilkan engine aktif:

```text
Native mysqldump
```

atau:

```text
Laravel Logical Export
```

System harus memilih engine yang benar berdasarkan hosting capability.

# 25. DATABASE TABLE SCOPE

Logical backup harus mencakup seluruh tabel aplikasi yang diperlukan.

Jangan hanya backup tabel inti dan melupakan:

- attendance
- schedule
- leave
- overtime
- notifications
- audit logs
- settings

# 26. SCHEMA COMPATIBILITY

Restore harus memeriksa:

- backup format version;
- app version;
- migration/schema fingerprint.

Jangan restore backup dengan schema incompatible secara otomatis.

Jika incompatible:

```text
Backup dibuat dengan versi database yang berbeda dan tidak dapat direstore secara otomatis.
```

# 27. BACKUP MANUAL

Button:

```text
Backup Sekarang
```

Owner memilih:

```text
○ Database Saja
○ Full Backup
```

Kemudian:

```text
[ Mulai Backup ]
```

Disable double submit.

# 28. BACKUP STATUS

Status:

```text
Creating
Completed
Failed
```

Jika gagal, technical error masuk log server.

# 29. DOWNLOAD BACKUP

Hanya Owner yang boleh download backup.

Download melalui authorized route.

Gunakan private/no-store response dan attachment disposition.

# 30. DELETE BACKUP

Owner dapat menghapus backup.

Wajib confirmation.

Delete harus:

- menghapus physical file;
- update backup record;
- audit action.

# 31. RESTORE — HIGH RISK ACTION

Restore hanya Owner.

Restore harus dianggap destructive/high-risk operation.

# 32. RESTORE FLOW

Flow wajib:

```text
Pilih Backup
↓
Validate Backup
↓
Tampilkan Informasi Backup
↓
Owner Re-Authentication
↓
Konfirmasi
↓
Buat PRE-RESTORE BACKUP
↓
Maintenance / Lock Mutating Operations
↓
Restore
↓
Integrity Validation
↓
Clear Relevant Cache
↓
Exit Maintenance
↓
Success / Safe Failure
```

Jangan skip pre-restore backup.

# 33. OWNER RE-AUTHENTICATION

Sebelum restore:

Owner wajib memasukkan password account aktif.

Optional confirmation phrase:

```text
RESTORE
```

boleh ditambahkan.

# 34. PRE-RESTORE BACKUP

Sebelum restore system otomatis membuat:

```text
Pre-Restore Safety Backup
```

Jika pre-restore backup gagal:

RESTORE HARUS DIBATALKAN.

# 35. RESTORE DATABASE

Jika logical backup:

- validate schema compatibility;
- use transaction sebanyak mungkin;
- handle FK constraints secara terkontrol;
- restore deterministic;
- validate record counts.

Jangan menjalankan arbitrary SQL dari upload yang tidak trusted.

# 36. RESTORE FILES

Untuk Full Backup restore:

- attendance selfies
- leave attachments
- branding assets

Extraction hanya ke expected private directories.

Jangan overwrite source code.

# 37. ZIP SLIP PROTECTION

Archive entry seperti:

```text
../../public/index.php
../../.env
```

HARUS ditolak.

Normalize path sebelum extract.

# 38. BACKUP UPLOAD FOR RESTORE

Boleh sediakan Upload Backup.

Validation:

- zip;
- size max;
- manifest present;
- checksum;
- format version;
- schema compatibility.

Jangan menerima ZIP arbitrary.

# 39. RESTORE EXISTING BACKUP

Preferred flow utama:

restore dari backup record yang sudah tersimpan server.

# 40. MAINTENANCE DURING RESTORE

Saat restore:

hindari employee melakukan check-in atau Admin mengubah data bersamaan.

Gunakan maintenance mode atau application lock yang kompatibel shared hosting.

# 41. RESTORE FAILURE

Jika restore gagal:

- log error;
- jangan mengklaim sukses;
- attempt safe rollback jika memungkinkan;
- pertahankan pre-restore backup;
- tampilkan recovery info yang jelas.

# 42. RESTORE AUDIT

Audit:

```text
backup.created
backup.downloaded
backup.deleted
backup.validated
restore.started
restore.completed
restore.failed
branding.updated
```

Jangan audit password re-auth.

# 43. SCHEDULED BACKUP

Tambahkan:

```text
Backup Otomatis        ON/OFF
```

Frequency minimal:

```text
Daily
Weekly
```

Fields:

- Frequency
- Time
- Day of Week jika weekly
- Backup Type
- Retention Count
- Enabled

# 44. SCHEDULE EXAMPLE

```text
Backup Otomatis    [ ON ]

Frekuensi
[ Setiap Hari ▼ ]

Waktu
[ 02:00 ]

Jenis Backup
[ Full Backup ▼ ]

Retensi
[ 14 ] backup terakhir

[ Simpan Pengaturan ]
```

Timezone:

```text
Asia/Jakarta
```

# 45. LARAVEL SCHEDULER

Gunakan Laravel Scheduler.

Shared hosting Cron:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Aplikasi menentukan apakah backup perlu dibuat.

# 46. DUPLICATE SCHEDULE PROTECTION

Scheduled backup tidak boleh duplicate akibat multiple schedule run.

Gunakan lock / withoutOverlapping / idempotency slot.

# 47. RETENTION POLICY

Owner dapat menentukan retention count wajar.

Contoh min/max:

```text
min 3
max 100
```

Cleanup hanya menghapus backup yang melebihi retention.

# 48. PROTECTED BACKUPS

Pre-restore safety backup diberi protection flag/label.

Jangan langsung dihapus oleh retention.

# 49. STORAGE CAPACITY

Tampilkan:

- Total Backup Count
- Total Backup Size
- Latest Backup
- Latest Successful Scheduled Backup

Free disk space optional jika environment mendukung.

# 50. FAILED BACKUP RETENTION

Failed backup tidak dianggap valid.

Partial file harus dibersihkan dengan aman.

# 51. BACKUP NOTIFICATION

Gunakan Notification module.

Notify Owner ketika:

- scheduled backup gagal;
- restore selesai;
- restore gagal.

Success notification scheduled backup optional.

# 52. SECRET EXCLUSION

Backup TIDAK BOLEH menyertakan:

- `.env`
- APP_KEY
- DB_PASSWORD
- MAIL_PASSWORD
- session secrets
- private API credentials

# 53. ENCRYPTION OPTIONAL

Jangan membuat custom encryption.

Prioritas:

- private storage;
- authorization;
- HTTPS download;
- checksum integrity.

# 54. BACKUP PERFORMANCE

Backup harus:

- tidak load semua binary ke memory sekaligus;
- stream archive bila memungkinkan;
- chunk logical database export;
- sadar keterbatasan shared hosting.

# 55. RESTORE VALIDATION

Setelah restore validate minimal:

- users count
- employees count
- locations count
- shifts count
- schedules count
- attendance count
- leave count
- overtime count
- notifications count
- settings presence
- referenced files exist

# 56. OWNER SESSION AFTER RESTORE

Jika restore mengubah user/session-related data:

invalidate current session dan arahkan login ulang secara aman.

# 57. BRANDING BACKUP

Full Backup harus mencakup uploaded branding assets dan branding settings.

# 58. UI BACKUP PAGE

Gunakan design SPRINT_16.

Desktop:
- summary cards
- actions
- history table

Mobile:
- cards/list
- modal/confirmation responsive

# 59. STATUS BADGES

Backup:

```text
Completed
Creating
Failed
```

Restore:

```text
Valid
Incompatible
Corrupted
```

Jangan hanya mengandalkan warna.

# 60. SECURITY AUTHORIZATION

Routes:

```text
/admin/settings/backups/*
/admin/settings/branding/*
```

harus protected server-side.

Restore: OWNER ONLY.

# 61. ACTION PROTECTION

Disable multiple rapid Backup/Restore requests + server-side lock.

# 62. NO BACKGROUND DAEMON REQUIREMENT

Jangan membutuhkan:

- Supervisor
- Redis
- PM2
- long-running queue worker

Gunakan scheduler/cron-compatible design.

# 63. TEST WAJIB — BRANDING

Tambahkan tests minimal:

```text
owner can update app name
employee cannot update branding
invalid color rejected
invalid logo MIME rejected
branding logo stored safely
dynamic manifest uses app name
dynamic manifest uses updated theme color
PWA icon route/path valid
branding change does not break login
```

# 64. TEST WAJIB — BACKUP

Tambahkan tests minimal:

```text
owner can create database backup
employee cannot create backup
backup stored outside public
backup manifest created
backup checksum created
backup excludes secrets
backup history lists completed backup
backup download requires owner authorization
backup delete requires owner authorization
backup delete removes physical file safely
failed backup not marked completed
```

# 65. TEST WAJIB — RESTORE

Tambahkan tests minimal:

```text
restore requires owner
restore requires password re-authentication
restore rejects invalid archive
restore rejects missing manifest
restore rejects checksum mismatch
restore rejects incompatible schema
restore rejects zip-slip path
pre-restore backup created before restore
restore aborts if pre-restore backup fails
restore creates audit log
```

Jangan restore development DB saat automated tests.

# 66. TEST WAJIB — SCHEDULED BACKUP

Tambahkan tests minimal:

```text
scheduled backup respects enabled flag
daily schedule resolves correct Asia/Jakarta time
weekly schedule resolves configured day
duplicate scheduled run prevented
retention deletes only excess backups
protected safety backup not deleted incorrectly
failed scheduled backup notifies owner
```

# 67. MANUAL VALIDATION — BRANDING

1. Login Owner.
2. Buka Pengaturan → Profil Aplikasi.
3. Ubah Nama Aplikasi.
4. Upload logo.
5. Upload icon.
6. Ubah primary color.
7. Save.
8. Refresh.
9. Pastikan Admin berubah.
10. Login Employee.
11. Pastikan Employee branding berubah.
12. Periksa browser title.
13. Periksa favicon.
14. Periksa `/manifest.webmanifest`.
15. Pastikan PWA metadata berubah.

# 68. MANUAL VALIDATION — BACKUP

1. Backup Database Only.
2. Pastikan Completed.
3. Validate backup.
4. Download.
5. Periksa archive.
6. Pastikan `.env` tidak ada.
7. Buat Full Backup.
8. Pastikan files relevant included.
9. Periksa checksum.
10. Restart app.
11. Pastikan backup history tetap ada.

# 69. MANUAL VALIDATION — RESTORE

Gunakan DATA TEST YANG AMAN.

1. Buat backup baseline.
2. Catat jumlah data.
3. Buat perubahan kecil.
4. Pilih backup baseline.
5. Restore.
6. Masukkan password Owner.
7. Pastikan pre-restore safety backup dibuat.
8. Confirm.
9. Pastikan Completed.
10. Login ulang jika session invalidated.
11. Pastikan data sesuai baseline.
12. Pastikan audit log tercatat.

# 70. MANUAL VALIDATION — SCHEDULE

Test scheduled backup dengan waktu development yang aman.

Pastikan:

- satu backup dibuat;
- tidak duplicate;
- retention bekerja;
- history update.

# 71. SHARED HOSTING DOCUMENTATION

Update:

```text
docs/SHARED_HOSTING.md
```

Tambahkan:

- Cron Scheduler
- backup storage path
- required PHP extensions ZIP/image
- mysqldump optional
- logical backup fallback
- file permissions
- storage quota
- restore restrictions

# 72. RULES UPDATE

Update:

```text
docs/RULES.md
```

Tambahkan rule:

## Backup Before Destructive Change

Sebelum perubahan production berisiko:

- buat backup;
- verify backup;
- jangan gunakan destructive database command.

## Restore Safety

Restore production hanya Owner dan wajib pre-restore backup.

# 73. REGRESSION

Pastikan tetap bekerja:

- Authentication
- Owner/Admin UI
- Employee Mobile UI
- PWA
- Employees
- Shift
- Scheduling
- GPS
- Selfie
- Attendance
- Check-in/out
- Leave
- Overtime
- Notifications
- Reports
- Audit Logs
- Attendance Correction
- Security hardening

# 74. FINAL TEST

Jalankan:

```bash
php artisan migrate
php artisan test
npm run build
```

JANGAN menjalankan destructive migration.

# 75. ACCEPTANCE CRITERIA

SPRINT 17.5 hanya PASS jika:

## Branding
- [ ] Nama aplikasi dapat diubah
- [ ] Nama singkat dapat diubah
- [ ] Logo dapat diubah
- [ ] Icon PWA dapat diubah
- [ ] Favicon dapat diubah
- [ ] Brand color dapat diubah
- [ ] Login mengikuti branding
- [ ] Admin mengikuti branding
- [ ] Employee Portal mengikuti branding
- [ ] PWA manifest mengikuti branding

## Backup
- [ ] Manual database backup bekerja
- [ ] Full backup bekerja
- [ ] Backup disimpan private
- [ ] Download owner-only
- [ ] Manifest tersedia
- [ ] SHA-256 checksum tersedia
- [ ] Secret tidak masuk backup
- [ ] Backup history bekerja
- [ ] Delete backup aman
- [ ] Shared hosting fallback tersedia jika mysqldump tidak ada

## Restore
- [ ] Restore owner-only
- [ ] Re-authentication wajib
- [ ] Backup divalidasi
- [ ] Checksum diverifikasi
- [ ] Schema compatibility diverifikasi
- [ ] Pre-restore safety backup wajib
- [ ] Restore database bekerja
- [ ] Full restore file bekerja
- [ ] Zip Slip/path traversal blocked
- [ ] Restore audit tercatat
- [ ] Failure handling aman

## Scheduled Backup
- [ ] Scheduled backup ON/OFF
- [ ] Daily bekerja
- [ ] Weekly bekerja
- [ ] Time Asia/Jakarta
- [ ] Duplicate run dicegah
- [ ] Retention bekerja
- [ ] Failed scheduled backup dapat notify Owner
- [ ] Cron shared hosting documented

## Regression
- [ ] Existing development data tetap utuh
- [ ] Security Sprint 17 tetap PASS
- [ ] PWA tetap PASS
- [ ] Attendance tetap PASS
- [ ] Full automated tests PASS
- [ ] `npm run build` PASS

# OUTPUT WAJIB SETELAH SELESAI

Berikan Completion Report:

1. Files created/changed.
2. Migration yang dibuat.
3. Backup architecture.
4. Database backup engine yang digunakan.
5. Fallback jika mysqldump tidak tersedia.
6. Backup archive structure.
7. Private storage path.
8. Restore safety flow.
9. Pre-restore backup behavior.
10. Scheduled backup configuration.
11. Retention behavior.
12. Branding settings yang tersedia.
13. PWA manifest changes.
14. Security protections.
15. Automated test result.
16. `npm run build` result.
17. Manual validation result.
18. Existing data persistence status.
19. Known limitations.

JANGAN mengerjakan SPRINT_18 setelah selesai.

Tunggu instruksi berikutnya.
