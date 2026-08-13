# SPRINT 03 — Store Location & Attendance Settings

## Tujuan

Mendefinisikan lokasi toko SELON BEAUTY dan pengaturan dasar absensi.

Sprint ini belum melakukan check-in nyata.

## 1. Attendance Location

Implementasikan:
```text
attendance_locations
```

Field minimal:
```text
id
name
address
latitude
longitude
radius_meters
max_accuracy_meters
is_active
created_at
updated_at
```

## 2. Validation

Latitude:
```text
-90 sampai 90
```

Longitude:
```text
-180 sampai 180
```

Radius:
```text
> 0
```

Max accuracy:
```text
> 0
```

## 3. Multi-location Ready

MVP cukup memakai satu lokasi aktif untuk SELON BEAUTY.

Tetapi schema/code jangan mengunci aplikasi secara permanen hanya satu lokasi.

## 4. Geofence Service

Buat service terpisah, misalnya:
```text
GeofenceService
```

Server harus mampu:
- menerima lat/lng employee;
- menerima lat/lng toko;
- menghitung jarak meter;
- menentukan inside/outside radius.

Gunakan Haversine atau perhitungan geodesic konsisten.

## 5. Attendance Settings

Siapkan pengaturan:
- timezone;
- checkout geofence required yes/no;
- selfie requirement;
- max GPS accuracy;
- radius.

Jangan simpan secret ke tabel setting.

## 6. UI

Owner/Admin:
```text
/admin/settings/attendance
```

Tampilkan:
- nama lokasi;
- alamat;
- latitude;
- longitude;
- radius;
- GPS accuracy limit;
- active status.

Tidak wajib integrasi Google Maps.

## 7. Tests

Minimal:
```text
valid coordinates accepted
invalid latitude rejected
invalid longitude rejected
inside-radius calculation correct
outside-radius calculation correct
inactive location cannot be used
```

# Acceptance Criteria

- [ ] lokasi dapat dibuat/edit
- [ ] validasi koordinat benar
- [ ] radius configurable
- [ ] accuracy configurable
- [ ] GeofenceService server-side tersedia
- [ ] unit test jarak PASS
- [ ] tidak membutuhkan API map berbayar
- [ ] tidak ada fake location
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
