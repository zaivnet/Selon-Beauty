# SPRINT 14 — Employee Mobile UI

## Tujuan

Menyempurnakan UI karyawan agar terasa seperti aplikasi mobile.

## 1. Mobile Shell

Dedicated employee layout.

Jangan gunakan sidebar desktop pada mobile.

## 2. Bottom Navigation

Maksimal 5:
```text
Home
Jadwal
Absen
Pengajuan
Profil
```

## 3. Home

Tampilkan:
- greeting;
- tanggal;
- shift hari ini;
- attendance status;
- CTA check-in/check-out;
- location status;
- ringkasan hari ini.

## 4. Attendance Screen

Flow harus jelas:
```text
Shift
Current time
GPS status
Camera status
Check In / Check Out
Result
```

## 5. Schedule

Mobile:
- date selector;
- list per hari;
- no tiny 7-column grid.

## 6. Request

Employee dapat:
- submit leave;
- submit overtime jika tersedia;
- lihat status.

## 7. Safe Area

Support:
```css
env(safe-area-inset-bottom)
```

agar bottom nav tidak bentrok dengan device UI.

## 8. Responsive Validation

Wajib cek:
```text
360px
390px
430px
768px
```

## 9. Tests / Manual QA

Minimal:
- no horizontal scroll;
- nav tidak overlap;
- modal fits mobile;
- forms usable;
- camera preview fits;
- long names do not break layout.

# Acceptance Criteria

- [ ] mobile-first layout
- [ ] bottom nav bekerja
- [ ] check-in flow jelas
- [ ] no overflow
- [ ] no hover-only actions
- [ ] semua CTA real
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
