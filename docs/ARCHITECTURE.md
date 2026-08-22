# SOFTWARE ARCHITECTURE

## 1. Architecture Style

Gunakan **modular monolith Laravel**.

Alasan:
- sederhana;
- mudah dipelihara;
- cocok shared hosting;
- tidak membutuhkan orchestration;
- deployment lebih mudah;
- cukup untuk skala SELON BEAUTY.

## 2. Stack

### Runtime
- PHP 8.3+ bila menggunakan Laravel 13.
- Laravel 13 target utama.
- MySQL/MariaDB.
- Apache atau LiteSpeed.
- HTTPS.

### Frontend
- Blade.
- Tailwind CSS.
- Alpine.js hanya untuk interaksi ringan.
- Vanilla JS untuk Geolocation, Camera, PWA.
- Chart.js bila dibutuhkan.

### Build
- Composer.
- Node.js + npm hanya untuk build assets.
- Tidak ada Node.js server runtime.

## 3. Hosting Compatibility Rules

DILARANG menjadikan komponen berikut mandatory:
- Docker;
- Redis;
- Memcached;
- PM2;
- Supervisor worker permanen;
- WebSocket server;
- MongoDB;
- Elasticsearch;
- Node.js runtime server.

Queue default:
- database atau sync sesuai kebutuhan.
- pekerjaan kecil dapat sync.
- email opsional dapat database queue hanya jika mekanisme cron queue runner disiapkan secara realistis.

## 4. Layering

```text
HTTP Request
  ↓
Middleware
  ↓
Controller
  ↓
Form Request Validation
  ↓
Application Service / Action
  ↓
Domain Rules
  ↓
Eloquent Repository/Model
  ↓
MySQL
```

Controller harus tipis.

Logic seperti validasi geofence, status terlambat, kalkulasi menit kerja jangan ditaruh langsung di Blade atau Controller besar.

## 5. Suggested App Structure

```text
app/
├── Actions/
│   ├── Attendance/
│   ├── Scheduling/
│   └── Leave/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Employee/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Services/
│   ├── AttendanceService.php
│   ├── GeofenceService.php
│   ├── ScheduleService.php
│   └── ReportService.php
├── Support/
└── View/
```

## 6. Attendance Flow

### Check-in

```text
Employee presses Check In
  ↓
Client requests browser GPS permission
  ↓
Client obtains coords + accuracy
  ↓
Client opens front camera
  ↓
Employee captures selfie
  ↓
POST check-in
  ↓
Server authenticates employee
  ↓
Server loads today's schedule
  ↓
Server validates no existing check-in
  ↓
Server validates lat/lng/accuracy
  ↓
Server calculates distance itself
  ↓
Server checks geofence
  ↓
Server stores optimized selfie
  ↓
Server uses server timestamp
  ↓
Server calculates late status
  ↓
Transaction commit
  ↓
Success response
```

Client-side distance is display-only. Server calculation is authoritative.

## 7. Geofence Calculation

Gunakan Haversine formula atau metode geospatial yang konsisten untuk menghitung jarak titik employee ke attendance location.

Simpan:
- latitude;
- longitude;
- accuracy_meters;
- calculated_distance_meters.

Reject jika:
- coordinates invalid;
- accuracy lebih buruk dari setting;
- distance di luar radius.

Owner dapat memiliki override melalui Attendance Correction, bukan bypass tersembunyi.

## 8. Timezone

Default:
`Asia/Jakarta`

Semua business calculation harus eksplisit memakai timezone aplikasi.

Database timestamps dapat menggunakan strategi framework yang konsisten, tetapi UI/report wajib menampilkan Asia/Jakarta.

## 9. Date Boundary

Untuk shift lintas tengah malam, attendance work date mengikuti tanggal schedule, bukan semata-mata tanggal kalender check-out.

## 9A. Effective Work Calendar

`EffectiveScheduleService` adalah source of truth untuk konteks `employee + work_date`:

```text
Employee Schedule Override
  ↓ jika tidak ada
Global Work Calendar (holiday / special working day)
  ↓ jika tidak ada
Regular Employee Schedule
```

Attendance resolver, monitoring, weekly schedule, leave validation, overtime start, dan report mengonsumsi hasil yang sama. Company/public holiday serta OFF tidak masuk denominator kehadiran. Actual attendance yang sudah ada tetap dipertahankan ketika kalender historis berubah. Global holiday tidak melakukan mass notification; perubahan override employee mengirim notification database dengan deep link ke jadwal mingguan.

### Home Outlet dan Work Outlet

`employees.outlet_id` adalah **HOME Outlet** permanen: outlet induk organisasi, batas pengelolaan employee, dan satu-satunya nilai yang boleh berubah melalui `EmployeeTransferService`. `work_schedules.work_outlet_id` dan `employee_schedule_overrides.work_outlet_id` menyimpan **Work Outlet** per tanggal; `EffectiveScheduleService` memilih override eksplisit, lalu jadwal reguler eksplisit, lalu HOME Outlet sebagai fallback kompatibilitas.

Check-in memakai Work Outlet efektif untuk geofence dan menyimpannya sebagai snapshot `attendance_records.outlet_id`. Check-out memakai snapshot record tersebut agar perubahan jadwal maupun Home Outlet sesudah check-in tidak mengubah sesi historis. Admin harus berwenang atas HOME Outlet employee sekaligus Work Outlet yang dipilih; selector outlet tidak pernah menjadi otorisasi.

### Attendance Participation / Current Workforce

`employees.attendance_enabled` menentukan kewajiban workforce saat ini dan tidak menentukan permission aplikasi. Role `owner`, `admin`, dan `employee` masing-masing dapat berpartisipasi atau tidak; Superadmin tetap mengikuti arsitektur existing dan dikecualikan oleh scope `currentAttendanceWorkforce()`.

`AttendanceParticipationService` mengubah flag dalam transaction, menolak disable bila masih ada attendance open atau overtime aktif, lalu membuat immutable audit dan database notification. `EffectiveScheduleService` menghasilkan state eksplisit `participates_in_attendance = false` tanpa menjadikan employee sebagai working day, holiday row, atau kewajiban check-in.

Selector dan agregasi current workforce menggunakan scope eksplisit, bukan global scope. Schedule/override tersimpan dan seluruh data historis tidak dihapus. Report atau correction historis yang memilih employee secara eksplisit tetap dapat membaca record lama; periode partisipasi historis belum di-versioning.

## 9B. Monthly Attendance Recap

`MonthlyAttendanceRecapService` menghitung rekap secara deterministic dan tidak menyimpan snapshot bulanan. Service melakukan batch query untuk employee, jadwal reguler, override, kalender, attendance current state, correction marker, approved leave, overtime request, dan overtime session; loop employee/tanggal tidak menjalankan query tambahan.

Source of truth:

```text
EffectiveScheduleService → kewajiban kerja, holiday/OFF, shift efektif
AttendanceStatusResolver → status harian
AttendanceRecord current state → waktu dan menit reguler yang sudah dikoreksi
LeaveRequest approved → izin/sakit/cuti
OvertimeRequest → requested/approved
OvertimeSession completed → actual/credited
```

Attendance rate adalah `(HADIR + TERLAMBAT) / effective_work_days × 100`. Izin, sakit, dan cuti tidak dianggap hadir. Nilai nol digunakan ketika tidak ada effective work day. Recap selalu mengikuti `work_date` anchor dan merupakan data kehadiran, bukan slip atau kalkulasi nominal payroll.

Readiness `NEEDS_REVIEW` hanya diberikan untuk data yang dapat ditindaklanjuti: attendance sudah check-in tetapi belum checkout, overtime session masih aktif, jadwal/shift historis belum lengkap, atau state attendance historis belum terselesaikan. Tanggal OFF/libur eksplisit tetap dianggap lengkap. Tidak ada freeze/closing pada sprint ini.

## 9C. Operational Exception Dashboard

`OperationalExceptionService` adalah source of truth read-only untuk dashboard admin dan halaman Pusat Perhatian. Service menerima tanggal berbasis `config('app.timezone')`, melakukan batch query, memakai `EffectiveScheduleService` serta `AttendanceStatusResolver`, dan memanggil rule review harian milik `MonthlyAttendanceRecapService`. Controller dan Blade tidak menghitung ulang status atau severity.

Kategori mencakup belum check-in, terlambat, belum check-out, tidak hadir, attendance perlu review, overtime aktif, overtime approved belum dimulai, approval leave/overtime pending, override hari ini, koreksi attendance terbaru, dan masalah backup/scheduler. Severity konsisten: `critical`, `warning`, atau `info`. Threshold missing checkout dan backup overdue berada di `config/operations.php` dan dapat dioverride melalui environment.

Exception tidak disimpan sebagai lifecycle baru: item hilang otomatis setelah source record diperbaiki. Dashboard tidak menyediakan approve/reject, correction, force finish, cancel, dismiss, atau mutation lain; setiap CTA hanya deep link menuju workflow existing yang sudah mempunyai authorization, audit, transaction, dan locking. Detail backup hanya dihitung dan ditampilkan untuk Owner/Superadmin sesuai permission matrix existing.

## 10. File Storage

Selfie:
```text
storage/app/private/attendance/{employee_id}/{YYYY}/{MM}/
```

Jangan menyimpan foto sensitif di folder public tanpa kontrol akses.

Akses gambar melalui authorized controller/temporary response.

Upload attachment:
- whitelist MIME;
- batas ukuran;
- random filename;
- jangan gunakan nama file asli sebagai path.

## 11. PWA

- `manifest.webmanifest`.
- icons.
- `display: standalone`.
- theme/background metadata.
- service worker.

Cache hanya:
- compiled CSS;
- compiled JS;
- logo/icon;
- static offline shell opsional.

Jangan cache secara permanen:
- dashboard authenticated HTML;
- attendance API;
- employee personal data;
- selfie;
- reports.

## 12. Scheduler

Gunakan Laravel Scheduler untuk pekerjaan terjadwal yang benar-benar perlu, misalnya:
- menghasilkan status absent setelah batas tertentu bila bisnis menginginkannya;
- housekeeping file temporary;
- notification digest.

Shared hosting menjalankan `php artisan schedule:run` via Cron.

## 13. Audit

Audit entity minimum:
- attendance corrections;
- employee activation/deactivation;
- shift changes;
- schedule changes;
- leave approval/rejection;
- critical settings.

Attendance correction dan overtime recovery memakai `audit_logs` yang sama sebagai immutable history. `reason` menyimpan alasan wajib dan `metadata` menyimpan konteks aman (`employee_id`, source, request reference, internal note). Snapshot disanitasi oleh `AuditLog::sanitizeData`; credential/token tidak ditampilkan.

Current-state marker (`corrected_at`, `corrected_by`, `completion_source`, `completed_by_user_id`) hanya mendukung provenance dan indikator UI. Resolver/report membaca current state dari attendance/overtime, bukan menghitung dari audit log. Employee tidak menerima IP, user-agent, atau catatan internal.

## 14. Failure Handling

Geolocation failure harus memberi pesan spesifik:
- permission denied;
- unavailable;
- timeout;
- low accuracy;
- outside radius.

Camera failure:
- permission denied;
- no compatible camera;
- stream failure.

Jangan hanya menampilkan "Terjadi kesalahan".

## 15. Authorization & User Role Architecture (Sprint 18.5)

- **Explicit Separation**: `job_titles.name` (Job Position) vs `users.role` (Application Access Control).
- **4 Final Roles**: `superadmin`, `owner`, `admin`, `employee`.
- **`EnsureUserRole` Middleware**: Supports `superadmin` bypass for all `/admin/*` operations while guarding against unauthorized roles.
- **`UserRoleService`**: Centralized service enforcing role validation, privilege escalation protection, last active Superadmin protection, session invalidation (`sessions` purge + `remember_token` refresh), and audit logging (`user.role_changed`).
- **CLI Administration**: `php artisan selon:create-superadmin` (alias `make:superadmin`) for safe Superadmin creation without default passwords.
- **Sensitive Access Matrix**:
  - Restore Backup: `superadmin` ONLY
  - Backup Create / Download / Schedule / Delete: `superadmin` + `owner`
  - Application Branding: `superadmin` + `owner`
  - Audit Logs: `superadmin` + `owner`

## 16. Overtime Session Architecture

- `attendance_records` hanya menyimpan satu sesi attendance reguler per employee dan `work_date`.
- `overtime_requests` menyimpan permintaan dan otorisasi: `requested_minutes` serta `approved_minutes`.
- `overtime_sessions` menyimpan aktivitas lembur aktual dan tidak membuat check-in kedua pada attendance reguler.
- Satu overtime request hanya dapat memiliki satu overtime session.
- Session hanya dapat dimulai setelah request approved dan attendance reguler untuk `work_date` tersebut selesai checkout.
- `actual_minutes` adalah selisih timestamp check-in dan check-out overtime yang authoritative dari server.
- `credited_minutes = min(actual_minutes, approved_minutes)`; durasi aktual tidak mengubah approval.
- Session cross-midnight tetap memakai `work_date` request/schedule asal dan tidak dipecah menjadi two record.
- Evidence selfie disimpan private pada `storage/app/private/overtime/{employee_id}/{YYYY}/{MM}/` dan hanya disajikan oleh authorized controller.

## 17. Monthly Attendance Closing & Period Lock Architecture

- **Metadata-Only Period State**: Periode bulanan mengelola dua status (`OPEN`, `CLOSED`) pada tabel `attendance_periods` tanpa duplikasi data historis atau pembuatan tabel snapshot payroll nominal.
- **Centralized Source of Truth**: `AttendancePeriodService` menjadi pusat otoritas status periode (`getOrCreatePeriod`, `isOpen`, `isClosed`, `assertPeriodOpen`).
- **Comprehensive Mutation Locks**: Saat periode ditutup (`CLOSED`), seluruh mutasi pada tanggal di periode meupun rentang tanggal yang bersinggungan langsung ditolak dengan `ValidationException`.
- **Close Eligibility Validation**: Periode hanya dapat ditutup jika `missing_checkout_count == 0` dan `active_overtime_count == 0`.
- **Role Authorization Policy**: Penutupan & pembukaan kembali periode khusus untuk `owner` dan `superadmin` dengan alasan minimal 5 karakter wajib. Admin biasa hanya memiliki hak baca.
- **Immutability & Audit Trail**: Penutupan dan pembukaan periode dicatat secara konsisten di `audit_logs` dan diikutsertakan dalam sistem Backup / Restore aplikasi.

## 18. Employee Shift Swap Architecture

- **Two-Party Swap Model**: Pertukaran jadwal dua arah antara Pemohon (Ayu) dan Tujuan (Dia) tanpa perantara multi-employee atau open marketplace.
- **Flexible Work Dates**: Mendukung penukaran tanggal yang sama maupun tanggal berbeda (`requester_work_date` & `target_work_date`).
- **`EffectiveScheduleService` Integration**: Resolusi jadwal efektif mendeteksi jadwal kerja aktif (`WORK`) secara authoritative sebelum pengajuan dan sebelum persetujuan akhir.
- **Applying Swaps via Overrides**: Saat disetujui Admin/Owner, dua data `EmployeeScheduleOverride` berjenis `work` dibuat otomatis dengan alasan `Shift swap #ID`. Master jadwal reguler dan master shift tidak pernah diubah secara destruktif.
- **Comprehensive Revalidation Guardrails**:
  - Period Lock check (`AttendancePeriodService::assertPeriodOpen`) menolak swap pada tanggal periode tertutup.
  - Absence of Attendance/Leave/Overtime conflicts.
  - Direct rejection jika terdapat `EmployeeScheduleOverride` manual sebelumnya.
  - Revalidasi perubahan jadwal (*stale schedule*) sebelum persetujuan akhir.
- **Audit Trail & Real-time Notifications**: Semua transisi status dicatat di `audit_logs` (`shift_swap.requested`, `target_approved`, `target_rejected`, `admin_approved`, `admin_rejected`, `cancelled`) dan notifikasi database real-time dikirimkan ke Pemohon, Tujuan, Owner/Superadmin, serta Admin yang berwenang atas HOME outlet terkait.

## 19. Admin Assigned Outlets

- `OutletScopeService` adalah satu-satunya otoritas cakupan outlet untuk operasi Admin.
- Owner dan Superadmin tetap memiliki global role scope. Admin mode `all` hanya memperluas cakupan outlet pada izin role Admin dan tidak memberikan kemampuan Owner/Superadmin.
- Admin mode `selected` menggunakan pivot `admin_outlet_assignments`; tanpa assignment aktif Admin gagal tertutup.
- Admin mode `all` tidak menyimpan satu pivot per outlet sehingga outlet aktif baru otomatis termasuk.
- `users.outlet_id` dipertahankan sebagai primary/default context kompatibilitas. Field ini bukan sumber otorisasi dan tidak dapat memberikan akses bila pivot kosong.
- Filter/session outlet adalah konteks query/UI per-user, bukan bukti otorisasi. Input outlet selalu disanitasi terhadap cakupan authoritative.
- `employees.outlet_id` adalah HOME outlet permanen. Nilai awal ditetapkan saat karyawan dibuat; perubahan setelahnya hanya melalui `EmployeeTransferService` agar blocker, histori transfer, transaksi, dan audit trail selalu dijalankan. Form Edit Data tidak dapat memindahkan HOME outlet.
- Sprint ini tidak menambahkan WORK outlet atau mengubah resolusi geofence/histori attendance.
