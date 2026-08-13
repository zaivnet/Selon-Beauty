# SPRINT 12 — Reports & Export

## Tujuan

Membuat laporan absensi yang dapat dipakai operasional SELON BEAUTY.

## 1. Filters

Minimal:
```text
date range
employee
status
```

## 2. Summary Per Employee

Hitung:
```text
present
late
absent
permission
sick
leave
total late minutes
total worked minutes
early leave minutes
approved overtime minutes
```

## 3. Detail Rows

Tampilkan:
- date;
- schedule;
- check-in;
- check-out;
- status;
- late;
- worked;
- overtime.

## 4. Rules

- OFF bukan absent;
- holiday bukan absent;
- unscheduled employee jangan salah hitung absent;
- approved leave konsisten;
- cross-midnight benar.

## 5. Export

Wajib minimal:
```text
CSV
```

Excel/PDF:
- boleh jika dependency stabil;
- harus kompatibel shared hosting;
- jangan menambah service eksternal.

## 6. Print View

Buat layout print-friendly.

## 7. Performance

- pagination;
- eager loading;
- query optimization;
- indexes jika perlu via migration.

## 8. Tests

Minimal:
```text
report totals correct
off not absent
leave totals correct
late minutes correct
worked minutes correct
filters affect results
CSV follows active filters
```

# Acceptance Criteria

- [ ] report real
- [ ] totals akurat
- [ ] filters bekerja
- [ ] CSV export bekerja
- [ ] print view rapi
- [ ] no N+1 obvious
- [ ] no fake report data
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
