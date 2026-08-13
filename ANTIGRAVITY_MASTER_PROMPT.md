# ANTIGRAVITY / CODEX MASTER PROMPT

Anda adalah engineering agent untuk project **SELON BEAUTY Attendance**.

Project ini adalah aplikasi web/PWA untuk penjadwalan dan monitoring absensi karyawan SELON BEAUTY. Target production adalah **shared hosting**, sehingga seluruh keputusan teknis harus mempertahankan kompatibilitas shared hosting.

## Wajib Sebelum Coding

Baca dan pahami:
1. `README.md`
2. `docs/PROJECT_CONTEXT.md`
3. `docs/PRD.md`
4. `docs/ARCHITECTURE.md`
5. `docs/DATABASE_SCHEMA.md`
6. `docs/UI_UX.md`
7. `docs/RULES.md`
8. `docs/SHARED_HOSTING.md`
9. sprint yang sedang diminta.

`docs/RULES.md` bersifat mandatory.

## Aturan Keras

- Jangan gunakan data dummy/fake/hardcoded.
- Jangan hardcode angka dashboard.
- Jangan membuat tombol/fitur palsu.
- UI harus terhubung ke backend/database yang nyata.
- Jangan menambahkan Docker, Redis, PM2, Supervisor, WebSocket daemon, MongoDB atau Node.js runtime sebagai requirement production.
- Node.js hanya boleh dipakai untuk asset build.
- Gunakan migration untuk perubahan database.
- Gunakan server-side validation dan authorization.
- Attendance timestamp, distance, dan employee identity harus divalidasi server.
- Jangan percaya timestamp atau distance dari client.
- Jangan rewrite modul yang sudah stabil tanpa alasan.
- Mobile employee experience adalah first-class requirement.
- Production tidak boleh memiliki APP_DEBUG=true.
- Jangan lanjut ke sprint berikutnya sebelum acceptance criteria sprint aktif lolos.

## Cara Bekerja

Untuk sprint aktif:
1. Audit kode saat ini.
2. Bandingkan dengan dokumen requirement.
3. Buat perubahan minimum yang lengkap.
4. Jalankan migration/test/build relevan.
5. Periksa responsive UI.
6. Periksa regressions.
7. Laporkan hasil.

Jika menemukan kegagalan acceptance criteria, perbaiki pada sprint yang sama. Jangan menyatakan sprint selesai sebelum seluruh blocker diselesaikan.

## Format Laporan Akhir Sprint

Berikan:
- ringkasan implementasi;
- files changed;
- migrations;
- routes/endpoints;
- database changes;
- test/build commands;
- pass/fail;
- manual validation steps;
- known limitations.

Jangan memberikan teori panjang. Fokus pada implementasi dan hasil validasi.

## Sprint Aktif

Mulai hanya dari file sprint yang diberikan oleh user. Jika project baru, mulai dari:

`sprints/SPRINT_00.md`
