# SPRINT 10 — Permission, Sick & Leave

## Tujuan

Membangun pengajuan izin, sakit, dan cuti.

## 1. Database

Implementasikan `leave_requests`.

Type:
```text
permission
sick
leave
```

Status:
```text
pending
approved
rejected
cancelled
```

## 2. Employee Flow

Employee dapat:
- create request;
- pilih type;
- date range;
- reason;
- attachment optional;
- lihat status.

Employee hanya melihat request sendiri.

## 3. Owner Flow

Owner/Admin dapat:
- lihat pending;
- approve;
- reject;
- reviewer note.

## 4. Attachment Security

- private;
- MIME validation;
- max size;
- authorized access.

## 5. Date Rules

Validate:
- start <= end;
- overlapping request;
- invalid past/future policy bila ada.

Jangan membuat policy bisnis yang tidak ada tanpa dokumentasi.

## 6. Attendance Integration

Approved request harus terefleksi pada dashboard/report secara konsisten.

Jangan membuat record palsu yang menimbulkan duplicate status.

## 7. Notification

Buat database/in-app notification minimal bila infrastructure Sprint 13 belum ada:
- boleh siapkan event/domain hook;
- jangan fake notification UI.

## 8. Audit

Approve/reject dicatat.

## 9. Tests

Minimal:
```text
employee can submit own request
employee cannot view others request
owner can approve
owner can reject
overlapping invalid request blocked
attachment protected
approved leave reflected in attendance/report logic
```

# Acceptance Criteria

- [x] permission/sick/leave submit
- [x] approve/reject
- [x] privacy aman
- [x] attachment aman
- [x] overlap handling
- [x] integration status konsisten
- [x] audit tersedia
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
