# SPRINT 05 — Employee Scheduling

## Tujuan

Membuat penjadwalan kerja harian/mingguan untuk karyawan.

## 1. Database

Implementasikan `work_schedules`.

Minimal:
```text
id
employee_id
work_date
shift_id
schedule_type
notes
created_by
updated_by
created_at
updated_at
```

Unique:
```text
employee_id + work_date
```

## 2. Schedule Types

Support:
```text
work
off
holiday
```

Rule:
- `work` wajib punya `shift_id`;
- `off` tidak memerlukan shift;
- `holiday` sesuai business rule.

## 3. Weekly Schedule UI

Owner/Admin dapat melihat:
- employee;
- tanggal;
- shift;
- OFF;
- holiday.

Desktop:
- weekly grid.

Mobile:
- list per tanggal.

## 4. Assign / Update

Owner dapat:
- assign shift;
- ganti shift;
- mark OFF;
- hapus schedule yang belum memiliki attendance jika sesuai rule.

## 5. Copy Previous Week

Fitur:
```text
Copy Previous Week
```

Harus:
- preview;
- detect conflict;
- tidak overwrite existing schedule secara diam-diam.

## 6. Employee View

Employee hanya dapat melihat jadwal sendiri.

## 7. Audit

Minimal log:
- created;
- changed;
- removed.

## 8. Tests

Minimal:
```text
owner can assign schedule
work requires shift
off does not require shift
employee-date unique
copy week avoids duplicate
employee sees only own schedule
```

# Acceptance Criteria

- [ ] schedule real tersimpan DB
- [ ] duplicate employee/date diblokir
- [ ] work/off/holiday jelas
- [ ] copy week aman
- [ ] employee privacy benar
- [ ] mobile responsive
- [ ] tidak ada jadwal dummy
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
