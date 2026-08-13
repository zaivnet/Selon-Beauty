# SPRINT 11 — Overtime

## Tujuan

Membangun pencatatan dan approval lembur tanpa payroll.

## 1. Database

Implementasikan `overtime_requests`.

Minimal:
```text
employee_id
work_date
requested_minutes
approved_minutes
reason
status
reviewed_by
reviewed_at
reviewer_note
```

## 2. Employee

Dapat:
- mengajukan lembur;
- lihat history;
- lihat approved minutes.

## 3. Owner/Admin

Dapat:
- review;
- approve;
- reject;
- menentukan approved minutes.

## 4. Validation

- work_date valid;
- requested_minutes > 0;
- approved_minutes wajar;
- employee tidak approve sendiri.

## 5. Attendance Context

Tampilkan attendance hari terkait:
- check-in;
- check-out;
- worked minutes.

Jangan otomatis menghitung uang lembur.

## 6. Tests

Minimal:
```text
employee can submit overtime
employee cannot approve own overtime
owner can approve
approved minutes persisted
invalid minutes rejected
employee cannot see others overtime
```

# Acceptance Criteria

- [ ] request overtime bekerja
- [ ] owner review bekerja
- [ ] approved minutes tersimpan
- [ ] no payroll calculation
- [ ] authorization aman
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
