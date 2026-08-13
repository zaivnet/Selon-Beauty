# SPRINT 06 — Attendance Core Engine

## Tujuan

Membangun engine absensi server-side sebelum GPS dan selfie diaktifkan penuh.

## 1. Database

Implementasikan `attendance_records`.

Ikuti `DATABASE_SCHEMA.md`.

Unique:
```text
employee_id + work_date
```

## 2. AttendanceService

Pisahkan business logic dari controller.

Minimal:
```text
AttendanceService
```
atau Actions sejenis.

## 3. Work Date Resolution

Untuk shift normal:
- work date = schedule date.

Untuk shift lintas tengah malam:
- check-out tetap dikaitkan ke work date schedule.

## 4. Server Timestamp

Wajib:
```text
check_in_at
check_out_at
```
menggunakan server time.

Jangan percaya waktu dari browser.

## 5. Late Calculation

Gunakan:
- shift start;
- grace period;
- server timestamp.

Hitung:
```text
late_minutes
```

## 6. Check-out Calculation

Hitung:
```text
worked_minutes
early_leave_minutes
overtime_minutes candidate
```

Perhatikan:
- break;
- cross-midnight;
- missing check-in.

## 7. Duplicate Protection

Harus aman dari:
- double tap;
- repeat request;
- refresh;
- concurrent submit.

Gunakan:
- unique constraint;
- transaction;
- application validation.

## 8. UI

Jangan mengklaim check-in production siap sebelum GPS/selfie Sprint 07/08.

Jika ada tombol sementara:
- disable;
- atau gunakan dev-only test route yang tidak exposed production.

## 9. Tests

Minimal:
```text
check-in creates one attendance record
duplicate check-in rejected
server timestamp authoritative
late calculation correct
on-time calculation correct
check-out worked minutes correct
early leave calculation correct
cross-midnight calculation correct
duplicate check-out rejected
```

# Acceptance Criteria

- [ ] attendance engine tersedia
- [ ] duplicate protection bekerja
- [ ] late_minutes benar
- [ ] worked_minutes benar
- [ ] early_leave benar
- [ ] cross-midnight benar
- [ ] transaction digunakan
- [ ] client time tidak dipercaya
- [ ] tests PASS
- [ ] build PASS

# Instruksi Wajib

Sebelum coding:

1. Baca `ANTIGRAVITY_MASTER_PROMPT.md`.
2. Baca seluruh dokumentasi mandatory di folder `docs/`.
3. Audit kode hasil sprint sebelumnya.
4. Jangan rewrite modul yang sudah stabil tanpa alasan teknis yang jelas.
5. Jangan mengerjakan sprint berikutnya.
6. Jangan membuat data dummy/fake/hardcoded.
7. Semua authorization dan validation kritis harus server-side.
8. Gunakan migration jika database berubah.
9. Pertahankan kompatibilitas shared hosting.
10. Jalankan test/build sebelum menyatakan sprint selesai.

# Output Wajib Setelah Selesai

Berikan laporan:

- files created/changed;
- migration/database changes;
- routes/endpoints;
- services/actions/policies/middleware;
- UI screens yang berubah;
- automated tests;
- hasil `php artisan test`;
- hasil `npm run build`;
- manual validation steps;
- known limitations.

Jangan menyatakan sprint selesai jika Acceptance Criteria masih gagal.
