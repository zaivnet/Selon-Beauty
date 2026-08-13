# SPRINT 01 — Authentication, Roles & Authorization

## Tujuan

Membangun sistem autentikasi yang aman untuk:
- Owner
- Admin
- Employee

Sprint ini hanya fokus pada Authentication, Role dan Authorization.
Jangan mengerjakan Employee Management atau fitur dari sprint berikutnya.

## 1. User Authentication
Implementasikan:
- Login (Email atau Phone)
- Logout
- Session authentication
- Remember me
- Password hashing menggunakan Laravel
- Session regeneration setelah login
- Login rate limiting (5 attempts per minute)

## 2. User Roles
Gunakan role:
- owner
- admin
- employee

Role disimpan pada database (`users.role`).
Authorization dilakukan server-side.

## 3. Access Rules
- **Owner**: `/admin/*` dan `/app/*`
- **Admin**: `/admin/*`
- **Employee**: `/app/*` (DILARANG mengakses `/admin/*`)
