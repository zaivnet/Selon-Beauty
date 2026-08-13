# SPRINT 09 — Attendance Monitoring Dashboard

## Tujuan

Membuat dashboard Owner untuk monitoring kehadiran harian.

## 1. KPI Hari Ini

Data real:
```text
Karyawan Aktif
Hadir
Terlambat
Belum Check-in
Izin/Sakit/Cuti
```

Dilarang hardcode.

## 2. Attendance List

Tampilkan:
- employee;
- schedule;
- check-in;
- check-out;
- status;
- late minutes;
- early leave;
- location evidence;
- selfie access.

## 3. Calculation Rule

Employee:
- OFF tidak dihitung absent;
- tanpa schedule jangan otomatis dianggap absent;
- leave approved harus konsisten.

## 4. Dashboard UI

Gunakan:
- card semantic;
- warna/tint profesional;
- icon berbeda;
- responsive.

Jangan membuat semua card putih polos.

## 5. Filters

Minimal:
- status;
- employee;
- date hari ini default.

## 6. Evidence View

Owner dapat membuka:
- GPS info;
- calculated distance;
- accuracy;
- selfie.

## 7. Tests

Minimal:
```text
dashboard totals match database
off employee not counted absent
unscheduled employee not counted absent
late count correct
attendance list authorization works
```

# Acceptance Criteria

- [x] KPI real
- [x] list real
- [x] status konsisten
- [x] selfie/location evidence accessible securely
- [x] mobile responsive
- [x] no fake chart
- [x] tests PASS
- [x] build PASS

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
