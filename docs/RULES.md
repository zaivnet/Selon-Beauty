# DEVELOPMENT RULES FOR ANTIGRAVITY / CODEX

Dokumen ini bersifat wajib.

## RULE 001 — Read Before Coding
Sebelum mengubah kode:
1. baca PROJECT_CONTEXT;
2. baca PRD;
3. baca ARCHITECTURE;
4. baca DATABASE_SCHEMA;
5. baca UI_UX;
6. identifikasi sprint aktif.

## RULE 002 — One Sprint at a Time
Jangan mengerjakan fitur dari sprint berikutnya kecuali menjadi dependency teknis yang sangat kecil dan dijelaskan.

## RULE 003 — No Dummy Data
DILARANG:
- fake employee;
- fake attendance;
- fake schedule;
- hardcoded dashboard numbers;
- fake chart dataset;
- placeholder rows yang terlihat sebagai data nyata.

Gunakan empty state.

## RULE 004 — No Fake Functionality
Tidak boleh ada tombol yang tampil aktif tetapi:
- tidak melakukan apa-apa;
- hanya console.log;
- hanya toast "success" tanpa persistence;
- menyimpan ke localStorage padahal seharusnya database.

## RULE 005 — Shared Hosting First
Production runtime tidak boleh membutuhkan:
- Docker;
- Node.js server;
- Redis;
- PM2;
- Supervisor;
- websocket daemon;
- MongoDB.

## RULE 006 — Database Through Migrations
Setiap perubahan schema wajib migration.
Jangan edit database manual sebagai solusi permanen.

## RULE 007 — Thin Controllers
Business logic gunakan Service/Action.
Validation gunakan Form Request jika sesuai.

## RULE 008 — Authorization
Setiap route/action sensitif harus dicek server-side.
Menyembunyikan menu bukan authorization.

## RULE 009 — Server Is Authoritative
Untuk attendance:
- timestamp server authoritative;
- distance dihitung server;
- status dihitung server;
- employee_id berasal dari authenticated user, bukan dipercaya dari input client.

## RULE 010 — Transactions
Check-in/check-out dan operasi multi-table kritis gunakan database transaction.

## RULE 011 — Prevent Duplicate Attendance
Gunakan unique constraint dan application validation.
Harus aman terhadap double tap / repeated request.

## RULE 012 — Secure Upload
- whitelist MIME;
- max size;
- random file name;
- no executable uploads;
- private storage;
- authorized serving.

## RULE 013 — No Secret in Git
Jangan commit:
- `.env`;
- APP_KEY;
- DB password;
- mail password;
- API secret.

## RULE 014 — Error Handling
Pesan user harus actionable.
Log detail teknis hanya di server.
Jangan expose stack trace production.

## RULE 015 — Mobile Is First-Class
Setiap sprint UI wajib dicek mobile.
Jangan menunda seluruh responsive work sampai sprint terakhir.

## RULE 016 — Accessibility
- semantic button;
- label form;
- keyboard focus desktop;
- touch target mobile;
- status bukan warna saja.

## RULE 017 — Query Hygiene
- pagination;
- eager loading;
- indexes;
- avoid N+1.

## RULE 018 — Tests
Minimal:
- authentication;
- role authorization;
- schedule constraints;
- geofence calculations;
- duplicate attendance protection;
- leave approval;
- report core calculations.

## RULE 019 — Do Not Rewrite Working Modules
Jika module lolos sprint sebelumnya, jangan rewrite tanpa alasan.
Perubahan harus minimal dan regresi diuji.

## RULE 020 — No Premature AI
Jangan implementasikan face recognition/liveness pada MVP.

## RULE 021 — Production Mode
Production:
- APP_DEBUG=false;
- secure session/cookie sesuai HTTPS;
- optimized autoload/config;
- correct storage permissions;
- logs tidak public.

## RULE 022 — Completion Report
Setiap sprint selesai harus melaporkan:
- files changed;
- migrations;
- routes;
- UI screens;
- tests run;
- passed/failed;
- known limitations;
- manual validation steps.

## RULE 023 — Stop on Failed Acceptance Criteria
Jika acceptance criteria gagal:
- perbaiki pada sprint yang sama;
- jangan mengklaim sprint selesai;
- jangan lanjut sprint.

## RULE 024 — Preserve Existing Data
Setiap migration, seeder, maupun refaktorisasi WAJIB mempertahankan data nyata yang sudah ada di database development/production. DILARANG menghapus, menimpa, atau melakukan truncate pada tabel yang berisi data manual tanpa rencana migrasi dan persetujuan eksplisit.

## RULE 025 — Production Seed
Tidak ada demo seeder.
Initial owner tidak boleh menggunakan password default hardcoded.

## RULE 026 — Strict Prohibition of Destructive Database Commands
1. DILARANG KERAS menjalankan command berikut terhadap database development (`selon_beauty_attendance`) maupun production:
   - `php artisan migrate:fresh`
   - `php artisan migrate:fresh --seed`
   - `php artisan db:wipe`
   - `php artisan migrate:reset`
   - `php artisan migrate:refresh`
2. Untuk migrasi skema database baru, hanya diperbolehkan menggunakan `php artisan migrate`.
3. Automated test (PHPUnit / `php artisan test`) WAJIB berjalan di database terpisah (`DB_CONNECTION=sqlite` dengan `DB_DATABASE=:memory:`), dan DILARANG menyentuh atau meriset database development utama.

## RULE 027 — Backup Before Destructive Change
Sebelum melakukan perubahan struktur database atau deployment produksi berisiko:
1. Buat backup manual melalui menu Backup & Restore (`/admin/settings/backups`);
2. Verifikasi status completed dan checksum SHA-256 backup;
3. Jangan pernah menggunakan destructive database command.

## RULE 028 — Restore Safety
Operasi Restore di lingkungan produksi atau development hanya diizinkan untuk role Superadmin dengan konfirmasi otentikasi ulang password. Sistem wajib secara otomatis membuat **Pre-Restore Safety Backup** sebelum mengeksekusi restore data.

## RULE 029 — Job Title Is Not Authorization
Jabatan (`job_titles.name`) adalah posisi operasional kerja karyawan (contoh: Kasir, Stylist, Admin Toko). Jabatan DILARANG digabungkan atau dijadikan penentu otorisasi/hak akses aplikasi. Hak akses aplikasi WAJIB ditentukan secara eksklusif oleh field `users.role` (`superadmin`, `owner`, `admin`, `employee`).

## RULE 030 — Least Privilege Role Assignment & Privilege Escalation Guard
1. Karyawan baru default role `employee`.
2. Superadmin berwenang mengelola role Owner, Admin, dan Employee.
3. Owner berwenang mengelola role Admin dan Employee, dan DILARANG menetapkan role Superadmin atau Owner.
4. Admin dan Employee DILARANG mengelola atau menaikkan role pengguna.
5. Permintaan perubahan role yang tidak sesuai hirarki privilege WAJIB ditolak server-side (403 / 422).

## RULE 031 — Superadmin Protection Policy
Minimal satu akun Superadmin yang aktif (`is_active = true`) WAJIB selalu tersedia di dalam sistem. Tindakan yang menyebabkan jumlah Superadmin aktif menjadi nol (seperti menurunkan role Superadmin terakhir, menonaktifkan akun Superadmin terakhir, atau menghapus Superadmin terakhir) DILARANG keras dan WAJIB ditolak server-side.

## RULE 032 — First-Run Setup & Zero Default Credentials
1. Fresh install dan production install DILARANG memiliki default user, dummy employee, atau default password hardcoded (`admin@admin.com`, `password123`, dll).
2. Ketika aplikasi baru di-install dan belum memiliki Superadmin aktif (`is_active = true`), aplikasi mengarahkan akses unauthenticated ke `/setup`.
3. Role Superadmin (`role = 'superadmin'`) dan status aktif (`is_active = true`) untuk akun setup pertama WAJIB ditentukan secara server-side dan kebal terhadap forged request input.
4. Setelah Superadmin aktif pertama berhasil dibuat, endpoint `/setup` WAJIB dikunci secara server-side.
5. Setelah Sprint 18.5.5 selesai, destructive database commands (`migrate:fresh`, `db:wipe`, `migrate:reset`) KEMBALI DILARANG KERAS pada database yang berisi data nyata.

## RULE 033 — Password Recovery & Session Revocation
1. Password recovery menggunakan **secure email reset link** dengan token expiration 60 menit dan bersifat single-use.
2. **No User Enumeration**: Response publik forgot password WAJIB berupa pesan generik ("Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password") dan DILARANG membocorkan keberadaan akun.
3. **No Plaintext Secrets**: Password, reset token, dan kredensial SMTP DILARANG disimpan atau dicatat dalam log secara plaintext.
4. **Session Revocation**: Proses reset password yang berhasil WAJIB secara otomatis mencabut (*invalidate*) seluruh sesi HTTP lama milik target user.
5. **Domain Agnostic**: URL reset password WAJIB digenerate secara dinamis dari `APP_URL` / request host tanpa hardcoded hostname.
6. **Administrative Fallback**: Reset password langsung untuk pengguna lain hanya diizinkan untuk role Superadmin dengan wajib mengonfirmasi ulang password Superadmin pribadi (*re-authentication*).

## RULE 034 — Separate Overtime Session
1. Attendance reguler tetap satu record per employee/work date dan tidak boleh dipakai untuk check-in lembur kedua.
2. Overtime aktual wajib disimpan di `overtime_sessions`, satu session maksimal untuk satu approved request.
3. Pada effective working day, employee wajib menyelesaikan checkout reguler sebelum memulai lembur. Pada holiday/OFF, approved overtime dapat dimulai tanpa attendance reguler.
4. Timestamp, GPS, geofence, dan perhitungan menit lembur bersifat server-authoritative.
5. `approved_minutes` adalah batas maksimum otorisasi, `actual_minutes` adalah durasi nyata, dan `credited_minutes` adalah nilai minimum dari keduanya.
6. Overtime cross-midnight tetap terikat pada `work_date` request asal.

## RULE 035 — Correction, Recovery, and Immutable Audit
1. `AttendanceRecord` dan `OvertimeSession` menyimpan current state; koreksi tidak membuat attendance kedua atau mengubah employee, work date, schedule relation, approved overtime, GPS, maupun evidence.
2. Koreksi admin wajib memakai transaction, row lock, alasan minimal 5 karakter, actor, before/after snapshot, action code, timestamp, dan referensi record pada `audit_logs`.
3. Status attendance tidak boleh diinput manual. Derived minutes dan status dihitung ulang melalui service/resolver menggunakan `config('app.timezone')`.
4. Missing checkout memakai action `attendance.checkout_recovered`; koreksi lain memakai `attendance.corrected`.
5. Cancel overtime tidak menghapus session dan menetapkan `actual_minutes = 0` serta `credited_minutes = 0` karena sesi dinyatakan tidak valid.
6. Force finish dan completed correction menghitung actual kembali; credited selalu `min(actual_minutes, approved_minutes)`.
7. Recovery admin tidak membuat selfie/GPS palsu dan tidak mengganti evidence asli.
8. `audit_logs` immutable pada aplikasi dan tidak memiliki route update/delete normal.

## RULE 036 — Effective Work Calendar
1. Jadwal efektif untuk `employee + work_date` hanya dihitung oleh `EffectiveScheduleService` dengan prioritas: employee override, kalender global, lalu jadwal reguler.
2. Company/public holiday dan override OFF tidak menghasilkan BELUM CHECK-IN atau TIDAK HADIR serta tidak masuk denominator attendance rate.
3. Override WORK wajib memakai shift existing. Special working day hanya memakai shift dari jadwal reguler WORK; jika tidak ada, admin wajib membuat override WORK dan sistem tidak boleh menebak shift.
4. Resolusi lintas tengah malam selalu memakai `work_date` awal shift sebagai anchor.
5. Perubahan kalender dan override wajib mempunyai alasan minimal 5 karakter serta audit before/after. Override material juga mengirim notification employee; kalender global tidak melakukan fan-out notification massal.
6. Attendance historis dan seluruh evidence tidak dihapus atau ditulis ulang saat kalender/override berubah.

## RULE 037 — Payroll-Ready Monthly Attendance Recap
1. Rekap bulanan bersifat deterministic dari source of truth current state dan tidak menyimpan nominal gaji, potongan, tunjangan, pajak, BPJS, THR, atau status closing payroll.
2. `effective_work_days` hanya mencakup effective schedule yang wajib bekerja; holiday, override OFF, dan regular OFF dikecualikan.
3. `present_days` mencakup HADIR dan TERLAMBAT; `late_days` adalah subset. Attendance rate adalah `present_days / effective_work_days × 100`, dan bernilai 0 jika denominator 0.
4. Menit regular worked tidak boleh dicampur dengan overtime. Requested/approved berasal dari request; actual/credited hanya berasal dari completed session. Approved tanpa session tetap actual/credited 0.
5. Attendance dan overtime lintas tengah malam masuk satu kali pada `work_date` asal.
6. READY bukan approval payroll. NEEDS REVIEW menandai missing checkout, overtime aktif, jadwal/shift historis belum lengkap, atau state historis unresolved. OFF/libur eksplisit bukan data bermasalah.
7. Employee hanya boleh membaca/export rekap miliknya; akses seluruh employee dilindungi role admin/owner/superadmin server-side.

## RULE 038 — Operational Exception Center
1. Exception operasional selalu derived dari current source of truth dan tidak mempunyai tabel, status resolved, atau mutation tersendiri.
2. Status attendance wajib berasal dari `EffectiveScheduleService` dan `AttendanceStatusResolver`; holiday, regular OFF, override OFF, dan approved leave tidak boleh muncul sebagai belum check-in/tidak hadir.
3. Shift lintas tengah malam tetap memakai `work_date` asal. Attendance open dari shift hari sebelumnya tetap dapat muncul sebagai belum checkout pada hari berjalan.
4. Severity hanya `critical`, `warning`, dan `info`; controller/Blade dilarang menghitung ulang severity atau threshold.
5. Dashboard dan Pusat Perhatian hanya boleh memberi deep link ke workflow existing. Approve, reject, correction, force finish, cancel, atau delete tidak boleh dilakukan langsung dari exception card.
6. Backup health dan link backup hanya boleh dikirim kepada Owner/Superadmin. Employee tidak boleh mengakses dashboard operasional.
7. Membuka dashboard tidak membuat notification atau audit log baru.

## RULE 039 — Attendance Participation
1. Role aplikasi, status employment/account, dan partisipasi attendance adalah tiga konsep terpisah. Owner, Admin, dan Employee masing-masing valid dengan `attendance_enabled` ON maupun OFF.
2. Current workforce hanya mencakup Employee aktif dengan `attendance_enabled = true`; Superadmin mempertahankan behavior existing dan tetap di luar workforce attendance.
3. Participation OFF mencegah attendance, leave, overtime, schedule, dan override baru secara server-side, tetapi tidak menonaktifkan login, mengubah role/jabatan, atau menghapus data.
4. Jadwal dan override tersimpan tidak dihapus saat participation OFF dan kembali dapat efektif setelah ON. Attendance, leave, overtime, correction, dan recovery historis tetap dapat diakses sesuai otorisasi.
5. Participation tidak dapat dimatikan saat attendance belum checkout atau overtime session masih aktif. Sistem tidak boleh auto-checkout atau auto-cancel.
6. Disable membutuhkan alasan minimal 5 karakter. Perubahan material membuat audit before/after dan notification; no-op tidak membuat audit maupun notification.
7. `attendance_enabled` adalah current-state flag dan belum menyimpan periode partisipasi historis. Report historis eksplisit membaca record yang telah tersimpan, sedangkan selector default hanya menampilkan current workforce.
