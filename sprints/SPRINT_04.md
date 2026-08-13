# SPRINT 04 — Shift Management

## Tujuan

Membuat pengelolaan shift kerja.

## 1. Database

Implementasikan `shifts` sesuai schema.

Minimal:
```text
id
name
code
start_time
end_time
check_in_open_minutes_before
check_in_close_minutes_after
check_out_open_minutes_before
grace_period_minutes
break_minutes
crosses_midnight
is_active
created_at
updated_at
```

## 2. CRUD Shift

Owner/Admin dapat:
- tambah;
- edit;
- aktif/nonaktif;
- lihat daftar.

## 3. Shift Code

Wajib unik.

Contoh format:
```text
PAGI
SIANG
FULL
```

Jangan seed otomatis.

## 4. Cross Midnight

Support shift seperti:
```text
22:00 - 06:00
```

Jangan salah menghitung sebagai durasi negatif.

## 5. Grace Period

Contoh:
```text
Shift mulai 09:00
Grace 5 menit
```

09:05 masih on time, 09:06 late 1 menit.

Logic detail akan digunakan Sprint 06.

## 6. Check-in Window

Contoh:
```text
check_in_open_minutes_before = 60
check_in_close_minutes_after = 120
```

Simpan definisinya dengan jelas.

## 7. Deletion Rules

Jika shift sudah dipakai schedule:
- jangan hard delete;
- gunakan `is_active=false`.

## 8. UI

Desktop:
- table;
- search/filter;
- status badge;
- edit.

Mobile:
- cards/list.

## 9. Tests

Minimal:
```text
shift can be created
shift code unique
invalid time settings rejected
cross-midnight supported
used shift cannot be hard deleted
inactive shift excluded from new scheduling
```

# Acceptance Criteria

- [ ] CRUD shift bekerja
- [ ] code unique
- [ ] cross-midnight benar
- [ ] grace period tersimpan
- [ ] check-in window tersimpan
- [ ] active/inactive bekerja
- [ ] tidak ada shift dummy
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
