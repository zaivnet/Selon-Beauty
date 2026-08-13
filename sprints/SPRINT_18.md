# SPRINT 18 — Performance, Cleanup & Stability

## Tujuan

Meringankan aplikasi sebelum deploy ke shared hosting.

## 1. N+1 Audit

Periksa:
- dashboard;
- employee list;
- schedules;
- attendance;
- reports;
- notifications.

Gunakan eager loading bila sesuai.

## 2. Indexes

Tambahkan index via migration jika query penting membutuhkannya.

## 3. Pagination

List besar wajib paginated.

## 4. Selfie Storage

Audit:
- file size;
- resize;
- duplicate temporary files;
- unused files.

## 5. Frontend Bundle

Audit:
- unused JS;
- unused CSS;
- unused library;
- duplicate icon library.

## 6. Dead Code

Hapus:
- abandoned prototype;
- debug route;
- console logs tak perlu;
- unused component;
- unused controller;
- temporary files.

Jangan menghapus automated test yang masih berguna.

## 7. Dummy Data Audit

Pastikan tidak ada:
- fake chart data;
- seeded fake employee;
- fake attendance;
- placeholder metric.

## 8. Laravel Optimization

Pastikan kompatibel:
```text
config:cache
route:cache
view:cache
```

Jangan memaksakan jika ada route closure yang tidak kompatibel—perbaiki secara benar.

## 9. Regression

Jalankan seluruh test suite.

# Acceptance Criteria

- [ ] no obvious N+1
- [ ] indexes cukup
- [ ] pagination aktif
- [ ] no unused heavy dependency
- [ ] no dummy data
- [ ] no debug route
- [ ] all tests PASS
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
