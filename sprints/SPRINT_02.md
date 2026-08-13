# SPRINT 02 — Employee Management

## Tujuan

Membangun modul pengelolaan karyawan SELON BEAUTY menggunakan data nyata.

Sprint ini hanya fokus pada:
- data karyawan;
- jabatan;
- status aktif/nonaktif;
- akun login employee;
- profile dasar.

Jangan mengerjakan shift, jadwal, atau absensi.

## 1. Database

Implementasikan migration/model:

### `job_titles`

Minimal:
```text
id
name
description
is_active
created_at
updated_at
```

### `employees`

Minimal:
```text
id
employee_code
full_name
phone
email
job_title_id
join_date
status
profile_photo_path
notes
created_at
updated_at
deleted_at
```

Ikuti `DATABASE_SCHEMA.md`.

## 2. Employee Code

`employee_code` wajib unik.

Contoh format diperbolehkan:
```text
SB-001
SB-002
```

Tetapi jangan otomatis membuat employee dummy.

Owner boleh mengisi kode manual atau gunakan generator aman yang hanya berjalan saat membuat employee nyata.

## 3. CRUD Karyawan

Owner/Admin dapat:
- melihat daftar;
- menambah;
- melihat detail;
- mengedit;
- menonaktifkan;
- mengaktifkan kembali.

Jangan hard delete employee yang sudah mempunyai relasi data penting.

Gunakan:
- status inactive;
- soft delete bila sesuai schema.

## 4. Job Title

Owner/Admin dapat:
- membuat jabatan;
- edit;
- aktif/nonaktif.

Contoh jabatan bukan data seed wajib.

Jangan membuat:
```text
Kasir
Beautician
Admin
```
secara otomatis hanya untuk mengisi UI.

## 5. Akun Login Employee

Employee dapat memiliki akun `users`.

Saat Owner membuat akun employee:
- tautkan `users.employee_id`;
- role wajib `employee`;
- password harus hashed;
- email/phone unique jika digunakan login.

Jangan tampilkan password lama.

Jika Owner melakukan reset password:
- generate/masukkan password baru;
- hash;
- invalidasi session bila diperlukan.

## 6. Profile Photo

Opsional.

Aturan:
- validasi MIME;
- validasi ukuran;
- random filename;
- jangan gunakan path dari input user;
- jangan menerima executable file.

## 7. Employee List UI

Desktop:
- search;
- filter status;
- pagination;
- kolom penting;
- actions.

Mobile:
- ubah menjadi list/card;
- jangan memaksa tabel lebar;
- tidak ada horizontal overflow.

## 8. Empty State

Jika tidak ada employee:

```text
Belum ada karyawan.

Tambahkan karyawan pertama untuk mulai menggunakan SELON BEAUTY Attendance.
```

Dilarang menampilkan employee contoh.

## 9. Authorization

- Owner/Admin dapat manage employee.
- Employee tidak boleh membuka `/admin/employees*`.
- Employee tidak boleh melihat data employee lain.

## 10. Validation

Minimal:
- employee code required + unique;
- full name required;
- email valid jika diisi;
- phone valid sesuai rule aplikasi;
- join date valid;
- job title valid jika dipilih;
- status valid.

## 11. Tests

Minimal:
```text
owner can create employee
owner can update employee
employee code must be unique
employee cannot access employee management
inactive employee can be reactivated
employee account has employee role
profile upload validation works
```

# Acceptance Criteria

- [ ] CRUD employee bekerja
- [ ] CRUD job title bekerja
- [ ] employee_code unique
- [ ] akun employee dapat dibuat dengan aman
- [ ] authorization server-side
- [ ] inactive/active bekerja
- [ ] soft-delete/deactivation tidak merusak relasi
- [ ] profile upload aman
- [ ] search/filter/pagination memakai data DB
- [ ] mobile responsive
- [ ] tidak ada employee dummy
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
