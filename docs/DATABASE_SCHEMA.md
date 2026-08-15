# DATABASE SCHEMA

Gunakan migration Laravel. Jangan membuat schema manual yang tidak direpresentasikan oleh migration.

Semua FK harus mempunyai kebijakan delete/update yang eksplisit.

## 1. users

- id BIGINT PK
- employee_id BIGINT NULL FK -> employees.id
- name VARCHAR(150)
- email VARCHAR(190) NULL UNIQUE
- phone VARCHAR(30) NULL UNIQUE
- password VARCHAR(255)
- role VARCHAR(30) INDEX
- is_active BOOLEAN DEFAULT TRUE
- last_login_at TIMESTAMP NULL
- remember_token
- created_at
- updated_at

Roles:
- owner
- admin
- employee

Catatan: hindari ENUM DB bila membuat perubahan role menjadi sulit.

## 2. job_titles

- id
- name VARCHAR(100) UNIQUE
- description TEXT NULL
- is_active BOOLEAN
- timestamps

## 3. employees

- id
- employee_code VARCHAR(50) UNIQUE
- full_name VARCHAR(150)
- phone VARCHAR(30) NULL
- email VARCHAR(190) NULL
- job_title_id FK NULL
- join_date DATE
- status VARCHAR(30) INDEX
- attendance_enabled BOOLEAN DEFAULT TRUE INDEX
- profile_photo_path VARCHAR(255) NULL
- notes TEXT NULL
- timestamps
- softDeletes

Status:
- active
- inactive

Relasi user dibuat nullable agar record employee dapat dibuat sebelum kredensial login bila diperlukan.

`attendance_enabled` adalah partisipasi workforce saat ini, terpisah dari `employees.status`, status akses akun, jabatan, dan `users.role`. Nilai `false` tidak menghapus jadwal maupun data historis.

## 4. attendance_locations

- id
- name VARCHAR(150)
- address TEXT NULL
- latitude DECIMAL(10,7)
- longitude DECIMAL(10,7)
- radius_meters UNSIGNED INT
- max_accuracy_meters UNSIGNED INT DEFAULT 100
- is_active BOOLEAN INDEX
- timestamps

Constraint aplikasi:
- latitude -90..90
- longitude -180..180
- radius > 0
- max_accuracy > 0

## 5. shifts

- id
- name VARCHAR(100)
- code VARCHAR(30) UNIQUE
- start_time TIME
- end_time TIME
- check_in_open_minutes_before UNSIGNED INT DEFAULT 60
- check_in_close_minutes_after UNSIGNED INT DEFAULT 120
- check_out_open_minutes_before UNSIGNED INT DEFAULT 0
- grace_period_minutes UNSIGNED INT DEFAULT 0
- break_minutes UNSIGNED INT DEFAULT 0
- crosses_midnight BOOLEAN DEFAULT FALSE
- is_active BOOLEAN INDEX
- timestamps

## 6. holidays

- id
- date DATE UNIQUE
- type VARCHAR(30) INDEX (`public_holiday`, `company_holiday`, `special_working_day`)
- name VARCHAR(150)
- description TEXT NULL
- is_working_day BOOLEAN
- applies_to_all_employees BOOLEAN
- created_by FK users.id NULL
- timestamps

Tabel existing ini menjadi kalender kerja global. Satu tanggal mempunyai maksimal satu event global; scope lokasi belum diterapkan pada sprint ini.

## 7. work_schedules

- id
- employee_id FK INDEX
- work_date DATE INDEX
- shift_id FK NULL
- schedule_type VARCHAR(30) INDEX
- notes TEXT NULL
- created_by FK users.id
- updated_by FK users.id NULL
- timestamps

Unique:
- employee_id + work_date

schedule_type:
- work
- off
- holiday

Aturan:
- `work` wajib memiliki shift_id.
- `off` tidak perlu shift_id.
- `work_schedules` adalah jadwal reguler; hasil final selalu dihitung oleh `EffectiveScheduleService`.

## 7A. employee_schedule_overrides

- id
- employee_id FK INDEX
- date DATE INDEX
- override_type VARCHAR(20) INDEX (`work`, `off`)
- shift_id FK NULL (wajib untuk `work`)
- reason TEXT
- created_by FK users.id NULL
- timestamps

Unique: `employee_id + date`. Override tidak mengubah atau menghapus template/jadwal reguler.

## 8. attendance_records

- id
- employee_id FK INDEX
- work_schedule_id FK NULL
- work_date DATE INDEX
- attendance_location_id FK NULL
- status VARCHAR(30) INDEX

Check-in:
- check_in_at TIMESTAMP NULL
- check_in_latitude DECIMAL(10,7) NULL
- check_in_longitude DECIMAL(10,7) NULL
- check_in_accuracy_meters DECIMAL(10,2) NULL
- check_in_distance_meters DECIMAL(10,2) NULL
- check_in_selfie_path VARCHAR(255) NULL
- check_in_ip VARCHAR(45) NULL
- check_in_user_agent TEXT NULL

Check-out:
- check_out_at TIMESTAMP NULL
- check_out_latitude DECIMAL(10,7) NULL
- check_out_longitude DECIMAL(10,7) NULL
- check_out_accuracy_meters DECIMAL(10,2) NULL
- check_out_distance_meters DECIMAL(10,2) NULL
- check_out_selfie_path VARCHAR(255) NULL
- check_out_ip VARCHAR(45) NULL
- check_out_user_agent TEXT NULL

Calculated:
- late_minutes UNSIGNED INT DEFAULT 0
- early_leave_minutes UNSIGNED INT DEFAULT 0
- worked_minutes UNSIGNED INT DEFAULT 0
- overtime_minutes UNSIGNED INT DEFAULT 0
- is_manually_adjusted BOOLEAN DEFAULT FALSE
- notes TEXT NULL
- timestamps

Unique:
- employee_id + work_date

Status:
- present
- late
- absent
- permission
- sick
- leave

`early_leave` sebaiknya menjadi calculated flag, bukan mengganti status utama.

## 9. leave_requests

- id
- employee_id FK INDEX
- type VARCHAR(30) INDEX
- start_date DATE
- end_date DATE
- reason TEXT
- attachment_path VARCHAR(255) NULL
- status VARCHAR(30) INDEX
- reviewed_by FK users.id NULL
- reviewed_at TIMESTAMP NULL
- reviewer_note TEXT NULL
- timestamps

type:
- permission
- sick
- leave

status:
- pending
- approved
- rejected
- cancelled

## 10. overtime_requests

- id
- employee_id FK INDEX
- work_date DATE INDEX
- requested_minutes UNSIGNED INT
- approved_minutes UNSIGNED INT NULL
- reason TEXT
- status VARCHAR(30) INDEX
- reviewed_by FK users.id NULL
- reviewed_at TIMESTAMP NULL
- reviewer_note TEXT NULL
- timestamps

## 11. attendance_corrections

- id
- attendance_record_id FK INDEX
- requested_by FK users.id
- approved_by FK users.id NULL
- reason TEXT
- before_data JSON
- after_data JSON
- status VARCHAR(30)
- reviewed_at TIMESTAMP NULL
- timestamps

Untuk koreksi langsung oleh owner, tetap simpan record audit dengan status approved.

## 12. notifications

- gunakan Laravel notifications table bila fitur database notification dipakai.

## 13. audit_logs

- id
- user_id FK NULL INDEX
- action VARCHAR(100) INDEX
- auditable_type VARCHAR(190) NULL INDEX
- auditable_id BIGINT NULL INDEX
- before_data JSON NULL
- after_data JSON NULL
- ip_address VARCHAR(45) NULL
- user_agent TEXT NULL
- created_at TIMESTAMP

Audit log idealnya append-only dari sisi aplikasi.

Tambahan correction/recovery yang bersifat additive:
- `audit_logs.reason` TEXT NULL dan `audit_logs.metadata` JSON NULL;
- `attendance_records.corrected_at` TIMESTAMP NULL dan `corrected_by` FK users NULL;
- `overtime_sessions.corrected_at`, `corrected_by`, `completed_by_user_id`, dan `completion_source` untuk provenance current state.

Audit history tidak disalin ke kolom current-state dan evidence path tidak diganti oleh workflow correction.

## 14. app_settings

- id
- key VARCHAR(150) UNIQUE
- value TEXT NULL
- type VARCHAR(30)
- is_public BOOLEAN DEFAULT FALSE
- timestamps

Gunakan hanya untuk setting yang tidak lebih cocok menjadi kolom terstruktur.

Contoh:
- company_name
- timezone
- attendance_require_checkout_geofence
- selfie_quality

Jangan menyimpan secret seperti APP_KEY/password API di tabel setting.

## 15. Tabel `attendance_periods`

Menyimpan status penutupan periode absensi bulanan beserta metadata penguncian.

Kolom:
- `id` (bigint, PK)
- `year` (unsigned int, N)
- `month` (unsigned int, N)
- `status` (string, `open` | `closed`, default `open`)
- `closed_at` (timestamp, nullable)
- `closed_by` (foreignId -> users, nullable)
- `reopened_at` (timestamp, nullable)
- `reopened_by` (foreignId -> users, nullable)
- `close_reason` (text, nullable)
- `reopen_reason` (text, nullable)
- `timestamps`

Unique Index: `['year', 'month']`

## Indexes Penting

- employees(status)
- work_schedules(employee_id, work_date)
- attendance_records(employee_id, work_date)
- attendance_records(work_date, status)
- leave_requests(status, start_date, end_date)
- overtime_requests(status, work_date)
- audit_logs(user_id, created_at)

## Seed Policy

Production seed hanya boleh:
- role/default configuration minimum yang benar-benar dibutuhkan;
- tidak boleh membuat karyawan palsu;
- tidak boleh membuat attendance palsu;
- tidak boleh membuat jadwal palsu;
- tidak boleh membuat owner dengan password hardcoded.

Initial owner dibuat melalui proses setup aman atau Artisan command khusus.
