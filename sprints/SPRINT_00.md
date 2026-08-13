# SPRINT 00 — Project Foundation & Hosting Preflight

## Tujuan
Membuat fondasi proyek SELON BEAUTY Attendance yang bersih, production-oriented, dan sejak awal kompatibel dengan shared hosting.

## Kerjakan
- Inspeksi environment project.
- Tentukan versi Laravel berdasarkan PHP environment.
- Jika PHP >= 8.3, targetkan Laravel 13.
- Konfigurasi timezone `Asia/Jakarta`.
- Konfigurasi MySQL.
- Siapkan Blade + Tailwind + asset build.
- Buat struktur layout dasar admin dan employee tanpa fake metrics.
- Siapkan `.env.example` lengkap tanpa secret.
- Konfigurasi logging production-safe.
- Buat health/readiness page yang hanya menampilkan informasi aman.
- Siapkan route groups: guest, authenticated, admin/owner, employee.
- Siapkan struktur Service/Action sesuai `ARCHITECTURE.md`.
- Pastikan project dapat berjalan tanpa Docker/Redis/Node runtime.
- Buat empty dashboard shell yang membaca data real atau menampilkan empty state.
- Buat dokumentasi local setup dan production preflight.

## Jangan
- Jangan membuat employee contoh.
- Jangan membuat attendance contoh.
- Jangan membuat dashboard angka hardcoded.
- Jangan implementasi auth custom sebelum Sprint 01 kecuali scaffolding minimum.

## Acceptance Criteria
- App boot tanpa error.
- Database connection valid.
- Timezone benar.
- Asset build berhasil.
- Halaman dasar responsive.
- Tidak ada dummy data.
- `.env` tidak masuk Git.
- Tidak ada dependency runtime yang bertentangan dengan shared hosting.

# Instruksi Wajib

Sebelum mengerjakan sprint ini:
1. Baca seluruh file di `/docs`, terutama `RULES.md`.
2. Audit kondisi kode saat ini sebelum mengubah apa pun.
3. Jangan menghapus fitur yang sudah lolos sprint sebelumnya.
4. Jangan menambahkan data dummy/fake/hardcoded.
5. Jangan membuat tombol palsu atau UI tanpa backend yang diperlukan.
6. Gunakan arsitektur yang tetap kompatibel dengan shared hosting.
7. Jika menemukan bug/regresi dari sprint sebelumnya yang menghalangi sprint ini, perbaiki secara minimal dan dokumentasikan.
8. Jalankan test/validation relevan sebelum menyatakan sprint selesai.
9. Jangan lanjut ke sprint berikutnya.

# Output Wajib Setelah Selesai

Berikan laporan ringkas:
- files created/changed;
- migrations yang dibuat/dijalankan;
- routes/endpoints baru;
- fitur yang benar-benar berfungsi;
- test/command yang dijalankan;
- hasil test;
- manual validation yang harus saya lakukan;
- known limitation jika ada.

Jangan menulis "selesai" jika acceptance criteria masih gagal.
