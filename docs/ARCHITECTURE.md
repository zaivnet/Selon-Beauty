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
