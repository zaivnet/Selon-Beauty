# SPRINT 08 — Selfie Attendance

## Tujuan

Menambahkan bukti selfie pada check-in/check-out.

## 1. Camera

Gunakan:
```javascript
navigator.mediaDevices.getUserMedia()
```

Prefer:
```text
facingMode: user
```

untuk front camera.

## 2. Flow

```text
Open Camera
Capture
Preview
Retake
Confirm
Submit
```

Jangan upload otomatis sebelum user confirm kecuali ada alasan kuat.

## 3. File Security

Server:
- whitelist image MIME;
- max size;
- random filename;
- private storage;
- tidak menerima executable content.

## 4. Optimization

Compress/resize dengan kualitas wajar.

Tujuan:
- tetap cukup jelas untuk bukti;
- tidak membebani shared hosting.

## 5. Private Storage

Foto tidak boleh sekadar disimpan terbuka di:
```text
public/
```

Serve melalui authorized endpoint/controller.

## 6. Authorization

Owner/Admin:
- dapat melihat selfie employee.

Employee:
- hanya selfie miliknya jika UI menyediakan.

## 7. Attendance Integration

Jika selfie required:
- check-in tanpa selfie valid harus gagal;
- check-out tanpa selfie valid harus gagal.

## 8. Errors

Tangani:
```text
camera permission denied
no camera
stream error
capture failed
upload validation failed
```

## 9. Tests

Minimal:
```text
selfie required
invalid MIME rejected
oversized file rejected
private file protected
employee cannot access another employee selfie
owner can access authorized selfie
```

# Acceptance Criteria

- [x] camera mobile bekerja pada HTTPS-compatible browser
- [x] capture/retake/confirm bekerja
- [x] foto private
- [x] file validation aman
- [x] GPS + selfie + attendance terintegrasi
- [x] unauthorized access blocked
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
