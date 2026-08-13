# SPRINT 15 — PWA / Installable App

## Tujuan

Membuat SELON BEAUTY Attendance installable sebagai PWA.

## 1. Manifest

Buat:
```text
manifest.webmanifest
```

Minimal:
```text
name
short_name
start_url
display=standalone
theme_color
background_color
icons
```

## 2. Icons

Siapkan ukuran yang diperlukan.

Gunakan aset brand SELON BEAUTY.

## 3. Service Worker

Cache:
- compiled CSS;
- compiled JS;
- icons;
- logo;
- static shell ringan.

Jangan cache agresif:
- attendance POST;
- authenticated dashboard HTML;
- private data;
- selfie;
- reports.

## 4. Offline Behavior

Buat fallback sederhana:
```text
Anda sedang offline.
Absensi membutuhkan koneksi internet.
```

Jangan menyimpan check-in offline dan mensinkronkan diam-diam tanpa desain khusus.

## 5. Update Strategy

Pastikan versi asset baru bisa diterapkan.

Jangan membuat user terjebak cache lama.

## 6. Logout Privacy

Setelah logout:
- private page tidak boleh terbuka dari cache;
- browser back tidak boleh menampilkan private data yang masih usable.

## 7. HTTPS

Documentasikan bahwa production wajib HTTPS.

## 8. Tests

Manual/automated:
```text
manifest valid
service worker registered
standalone opens
offline shell works
private data not cached
logout privacy verified
```

# Acceptance Criteria

- [ ] PWA manifest valid
- [ ] service worker aktif
- [ ] installable browser compatible
- [ ] standalone UI rapi
- [ ] offline fallback aman
- [ ] no sensitive cache leak
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
