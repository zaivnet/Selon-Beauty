# SPRINT 16 — Admin UI/UX Polishing

## Tujuan

Meningkatkan kualitas visual dashboard Owner/Admin tanpa mengubah business logic.

## 1. Sidebar

- modern;
- collapsible;
- active state kuat;
- icon distinct;
- collapsed tooltip;
- no overlap.

## 2. Header/User Menu

- avatar;
- nama;
- role;
- logout;
- dropdown alignment;
- z-index benar;
- mobile responsive.

## 3. KPI Cards

Buat lebih premium:
- semantic tint;
- icon badge;
- hierarchy;
- subtle decorative detail.

Jangan semua card putih polos.

## 4. Tables

Desktop:
- clean;
- filter/search;
- pagination;
- actions konsisten.

Mobile:
- list/card.

## 5. Forms

- spacing;
- labels;
- validation;
- helper text;
- confirmation destructive action.

## 6. Empty / Loading States

Semua halaman utama:
- empty state;
- skeleton/loading;
- error state;
- success state.

## 7. Iconography

Gunakan satu library konsisten.

Icon tiap menu harus mudah dibedakan.

## 8. Responsive QA

Cek:
```text
360
390
430
768
1024
1366
1440
```

# Acceptance Criteria

- [ ] UI lebih premium
- [ ] sidebar/dropdown stabil
- [ ] icon distinct
- [ ] cards tidak polos
- [ ] no overflow
- [ ] tidak ada tombol rusak
- [ ] business logic tetap lolos
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
