# SPRINT 17 — Security & Audit Hardening

## Tujuan

Melakukan hardening keamanan sebelum production.

## 1. Route Audit

Audit seluruh route:
- guest;
- owner;
- admin;
- employee.

Pastikan server-side authorization.

## 2. IDOR

Test employee mencoba:
- employee ID lain;
- attendance lain;
- selfie lain;
- leave request lain;
- overtime lain.

Harus ditolak.

## 3. File Security

Audit:
- MIME;
- extension;
- size;
- storage;
- authorized serving.

## 4. Authentication Security

- CSRF;
- rate limit;
- session regeneration;
- inactive account;
- password hashing;
- logout session invalidation.

## 5. XSS / Input

Pastikan Blade escaping default.

Raw HTML hanya jika benar-benar diperlukan dan sanitized.

## 6. Audit Log

Implement/sempurnakan `audit_logs`.

Critical actions:
```text
employee activate/deactivate
shift changes
schedule changes
attendance correction
leave approve/reject
overtime approve/reject
settings changes
```

## 7. Attendance Correction

Implement bila belum:

Owner/Admin dapat koreksi:
- check-in;
- check-out;
- status.

Wajib:
```text
reason
actor
before_data
after_data
timestamp
```

## 8. Production Security

Pastikan dokumentasi:
```text
APP_ENV=production
APP_DEBUG=false
HTTPS
secure cookies
.env not public
storage not public
```

## 9. GPS Disclaimer

Jangan mengklaim geofencing adalah anti fake GPS absolut.

Geofence adalah kontrol verifikasi lokasi, bukan jaminan anti-spoof 100%.

## 10. Tests

Minimal:
```text
IDOR blocked
private selfie blocked
employee cannot correct attendance
owner correction audited
inactive user blocked
critical settings protected
```

# Acceptance Criteria

- [ ] route authorization audited
- [ ] IDOR blocked
- [ ] file access protected
- [ ] audit logs real
- [ ] attendance correction audited
- [ ] no secret exposure
- [ ] production security documented
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
