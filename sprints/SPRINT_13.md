# SPRINT 13 — Notifications

## Tujuan

Membuat notifikasi in-app ringan tanpa WebSocket/Redis requirement.

## 1. Database Notifications

Gunakan Laravel database notifications atau equivalent ringan.

## 2. Events

Minimal:
```text
leave submitted -> owner/admin
leave approved -> employee
leave rejected -> employee
overtime submitted -> owner/admin
overtime approved/rejected -> employee
attendance correction relevant -> employee
```

## 3. UI

Tampilkan:
- notification bell;
- unread count;
- list/page;
- mark as read.

## 4. Authorization

User hanya melihat notifikasi miliknya.

## 5. No Real-time Daemon

Jangan gunakan:
- websocket server;
- Redis;
- Pusher mandatory;
- Supervisor mandatory.

Polling ringan opsional tetapi tidak wajib.

## 6. Email

Opsional hanya jika SMTP tersedia.

Jangan membuat email delivery palsu.

## 7. Tests

Minimal:
```text
notification created for intended user
unread count correct
mark as read persists
employee cannot see others notification
app works without websocket
```

# Acceptance Criteria

- [ ] notifications real
- [ ] unread count real
- [ ] read state persisted
- [ ] authorization aman
- [ ] no websocket/redis dependency
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
