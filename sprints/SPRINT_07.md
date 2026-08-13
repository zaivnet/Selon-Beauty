# SPRINT 07 — GPS & Geofencing Attendance

## Tujuan

Mengaktifkan validasi GPS/geofence nyata untuk check-in/check-out.

## 1. Browser Geolocation

Gunakan:
```javascript
navigator.geolocation
```

State UI:
```text
requesting
permission denied
unavailable
timeout
low accuracy
outside radius
ready
```

## 2. Payload

Client dapat mengirim:
```text
latitude
longitude
accuracy
```

Tetapi server harus:
- validate;
- hitung ulang distance;
- menentukan inside/outside.

Client distance hanya untuk display.

## 3. Accuracy Rule

Jika:
```text
accuracy > max_accuracy_meters
```
reject.

Tampilkan alasan jelas.

## 4. Radius Rule

Jika:
```text
distance > attendance_location.radius_meters
```
reject.

## 5. Store Evidence

Simpan:
```text
latitude
longitude
accuracy
distance
IP
user agent
```

untuk check-in/check-out.

## 6. Security

Employee identity harus berasal dari authenticated session.

Dilarang percaya:
```text
employee_id
```
dari request body.

## 7. Checkout Geofence

Gunakan setting:
```text
attendance_require_checkout_geofence
```

Jika disabled, dokumentasikan behavior.

## 8. Tests

Minimal:
```text
inside radius accepted
outside radius rejected
poor accuracy rejected
invalid coords rejected
employee cannot submit attendance for another employee
server recalculates distance
```

# Acceptance Criteria

- [ ] browser GPS integrated
- [ ] server geofence authoritative
- [ ] outside radius blocked
- [ ] low accuracy blocked
- [ ] GPS evidence stored
- [ ] identity tampering blocked
- [ ] clear UI errors
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
