# SPRINT 18.6 — Forgot Password & Email Reset

## Tujuan

Tambahkan fitur **Lupa Password** menggunakan **email terdaftar** dengan mekanisme reset link yang aman, domain-agnostic, dan kompatibel dengan shared hosting.

Sprint ini dikerjakan setelah:

- SPRINT_18.5 — User Role Management & Access Control

Dan sebelum:

- SPRINT_19 — Shared Hosting Deployment

**JANGAN mengerjakan SPRINT_19 pada sprint ini.**

---

## 1. Business Flow

Gunakan flow:

```text
Login
  ↓
Lupa Password
  ↓
Masukkan email terdaftar
  ↓
Kirim link reset password
  ↓
User membuka email
  ↓
Klik link reset
  ↓
Masukkan password baru
  ↓
Konfirmasi password baru
  ↓
Password diperbarui
  ↓
Session lama di-invalidasi
  ↓
User login kembali
```

Gunakan mekanisme reset password Laravel yang aman dan existing-compatible.

Jangan membuat OTP SMS/WhatsApp pada sprint ini.

---

## 2. Login Page

Pada halaman login tambahkan:

```text
Lupa Password?
```

Link menuju halaman forgot password.

UI harus mengikuti design system existing dan responsive pada desktop/mobile/PWA.

---

## 3. Forgot Password Page

Buat halaman **Lupa Password**.

Isi minimal:

```text
Masukkan email yang terdaftar pada akun Anda.
Kami akan mengirimkan link untuk mengatur ulang password.
```

Field:

```text
Email
```

Button:

```text
Kirim Link Reset
```

Tambahkan:

```text
Kembali ke Login
```

---

## 4. Jangan Bocorkan User Existence

Response public tidak boleh membocorkan apakah email terdaftar atau tidak.

Jangan gunakan:

```text
Email tidak terdaftar.
```

Gunakan response generik:

```text
Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.
```

Frontend tidak boleh membedakan secara eksplisit antara email terdaftar dan tidak terdaftar.

---

## 5. Recovery Identity

Gunakan:

```text
users.email
```

sebagai recovery identity utama.

Pastikan:

- email unik;
- validasi server-side;
- konsisten dengan login account;
- tidak mengambil email dari Job Title atau field display lain.

Jika Employee memiliki email profile terpisah, recovery tetap menggunakan email account User yang benar.

---

## 6. Reset Token

Gunakan secure reset token.

Persyaratan:

- random dan secure;
- tidak disimpan plaintext jika framework sudah menyediakan hashing;
- single-use;
- mempunyai expiration;
- tidak dapat dipakai kembali setelah reset sukses;
- tidak dapat digunakan untuk user lain.

Preferred expiration:

```text
60 menit
```

Jika konfigurasi existing sudah mempunyai nilai aman yang berbeda, pertahankan dan dokumentasikan.

Jangan hardcode expiry di controller jika tersedia melalui config.

---

## 7. Reset Password Page

Setelah link valid dibuka, tampilkan:

```text
Password Baru
Konfirmasi Password Baru
```

Optional:

```text
Tampilkan Password
```

Gunakan design system existing.

---

## 8. Password Policy

Reuse password policy existing hasil security hardening.

Jangan membuat rule kedua yang berbeda.

Wajib:

- password dan confirmation cocok;
- minimum length mengikuti existing policy;
- password di-hash;
- password plaintext tidak pernah disimpan;
- password tidak pernah ditulis ke log.

---

## 9. Reset Success

Setelah reset berhasil:

1. update password;
2. invalidate reset token;
3. invalidate session lama target user;
4. regenerate remember token jika relevan;
5. buat Audit Log;
6. redirect ke Login.

Tampilkan:

```text
Password berhasil diperbarui. Silakan login menggunakan password baru.
```

Jangan auto-login setelah reset.

---

## 10. Invalidate Session Lama

Ini wajib.

Jika password di-reset, session lama target user tidak boleh terus memiliki akses.

Gunakan mekanisme yang sesuai dengan session driver project.

Jika session database digunakan, invalidate session target user secara aman.

Jangan logout semua user.

---

## 11. Rate Limiting

Tambahkan rate limiting pada forgot password request.

Proteksi minimal:

- per email;
- per IP;
- resend throttle.

Tujuan:

- mencegah spam email;
- mencegah abuse;
- mencegah reset request berulang berlebihan.

Gunakan Laravel RateLimiter/middleware jika sesuai architecture existing.

Jangan menambahkan Redis sebagai dependency.

---

## 12. Token Reuse

Scenario:

```text
User reset password sukses.
User mencoba memakai link reset yang sama lagi.
```

Expected:

```text
Link reset tidak valid / sudah digunakan.
```

---

## 13. Expired Token

Jika token expired:

```text
Link reset password sudah tidak berlaku.
Silakan meminta link reset yang baru.
```

Tambahkan button/link ke halaman Forgot Password.

---

## 14. Invalid Token

Token invalid/manipulated harus ditolak.

Jangan expose:

- token internal;
- stack trace;
- query;
- exception detail.

---

## 15. Inactive Account

Jika:

```text
is_active = false
```

password reset tidak boleh mengaktifkan account.

Account tetap inactive setelah reset dan tetap tidak dapat login.

Public forgot-password response tetap generic.

---

## 16. Superadmin Fallback Reset

Tambahkan fallback administratif:

```text
Superadmin → User/Employee → Reset Password
```

Tujuan:

- user kehilangan akses email;
- email salah;
- SMTP bermasalah.

Superadmin tidak boleh melihat password lama.

---

## 17. Administrative Reset Flow

Flow preferred:

```text
Klik Reset Password
  ↓
Re-authenticate Superadmin
  ↓
Masukkan Password Baru target user
  ↓
Konfirmasi Password
  ↓
Update password
  ↓
Invalidate session target
  ↓
Audit Log
```

---

## 18. Superadmin Re-authentication

Administrative password reset adalah sensitive action.

Wajib meminta:

```text
Konfirmasi password Superadmin
```

Reuse password-confirmation/re-auth mechanism existing dari security hardening.

---

## 19. Role Restriction

Gunakan policy:

```text
Superadmin:
- boleh direct reset password user lain

Owner:
- tidak boleh direct reset password user lain

Admin:
- tidak boleh direct reset password user lain

Employee:
- tidak boleh direct reset password user lain
```

Owner/Admin dapat meminta user menggunakan Forgot Password melalui email.

Server-side policy wajib menolak forged request.

---

## 20. Change Password Tetap Terpisah

Bedakan:

```text
Change Password
```

untuk user yang sudah login, dengan:

```text
Forgot Password
```

untuk user yang tidak dapat login.

Jangan mencampur route/policy keduanya.

---

## 21. Audit Log

Catat event:

```text
password_reset.requested
password_reset.completed
password_reset.admin_completed
```

Audit minimal:

- target user ID;
- actor ID untuk administrative reset;
- IP;
- user agent;
- timestamp;
- action.

DILARANG menyimpan:

- password;
- password confirmation;
- full reset token;
- SMTP password.

---

## 22. Email Content

Buat email bersih dan sesuai branding existing.

Subject contoh:

```text
Reset Password — {APP_NAME}
```

Isi:

```text
Kami menerima permintaan untuk mengatur ulang password akun Anda.

Klik tombol di bawah untuk membuat password baru.

[ Reset Password ]

Link ini memiliki masa berlaku terbatas.

Jika Anda tidak meminta reset password, abaikan email ini.
```

Jangan hardcode `SELON BEAUTY`.

Gunakan dynamic app name/branding dari Sprint 17.5.

---

## 23. Domain-Agnostic Reset URL

Ini wajib.

DILARANG hardcode:

```text
https://beauty.selon.my.id
https://selon.my.id
localhost
127.0.0.1
```

sebagai URL production pada source code reset password.

Gunakan framework URL generation/configuration.

Production hostname nantinya berasal dari environment:

```text
APP_URL
```

Jika domain berubah, source code tidak perlu diedit.

---

## 24. HTTPS

Production reset link wajib menggunakan HTTPS.

Jangan force localhost development menjadi production hostname.

Scheme production harus mengikuti production configuration.

---

## 25. SMTP Configuration

Update `.env.example` dan dokumentasi dengan key yang dibutuhkan:

```env
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

JANGAN:

- commit credential nyata;
- hardcode SMTP password;
- menaruh credential di Blade/JS;
- commit `.env`.

---

## 26. Shared Hosting Compatibility

Fitur harus bekerja tanpa:

- Redis;
- WebSocket;
- Supervisor;
- PM2;
- Docker;
- runtime Node server.

Gunakan SMTP biasa.

Untuk MVP shared hosting, reset email tidak boleh bergantung pada daemon queue yang harus hidup terus-menerus.

---

## 27. Synchronous Mail Preferred

Karena volume reset password rendah, preferred:

```text
Password reset email = synchronous
```

atau mekanisme lain yang tidak membutuhkan worker permanen.

Jangan menambahkan queue infrastructure baru hanya untuk forgot password.

---

## 28. SMTP Settings UI

Tidak wajib membuat SMTP Settings UI pada sprint ini.

Preferred:

```text
SMTP configuration via .env
```

Jika project sudah mempunyai secure encrypted SMTP configuration, boleh reuse.

Jangan membuat credential storage baru tanpa kebutuhan.

---

## 29. Mail Test

Sediakan cara aman untuk test email.

Boleh menggunakan custom Artisan command atau diagnostic existing, misalnya:

```bash
php artisan selon:test-mail recipient@example.com
```

Nama command boleh menyesuaikan architecture.

Jangan tampilkan SMTP password pada output.

---

## 30. Delivery Failure

Jika SMTP gagal:

- jangan tampilkan stack trace ke public user;
- jangan expose credential;
- log error teknis secara aman;
- public response tetap aman.

---

## 31. CSRF

Semua form POST tetap menggunakan CSRF protection.

Jangan disable CSRF pada forgot/reset password.

---

## 32. Open Redirect Protection

Jangan menerima arbitrary redirect URL dari request.

Setelah reset sukses, redirect hanya ke route internal Login.

---

## 33. UI State

Button `Kirim Link Reset` harus memiliki:

- loading state;
- disabled state selama submit;
- protection dari double submit.

---

## 34. Mobile UX

Test minimal viewport:

```text
360
390
430
768
1366
```

Halaman login, forgot password, dan reset password tidak boleh horizontal overflow.

---

## 35. PWA Security

Forgot/reset password tetap dapat dibuka dari PWA.

Tetapi:

- jangan cache reset token response;
- jangan cache reset password form secara agresif;
- jangan simpan password/token pada service worker cache.

Audit service worker caching rules untuk auth/reset routes.

---

## 36. No Sensitive Browser Storage

DILARANG menyimpan:

- password;
- reset token;
- SMTP credential;

ke:

```text
localStorage
sessionStorage
IndexedDB
```

---

## 37. Automated Tests — Forgot Password

Tambahkan tests:

```text
guest can open forgot password page
registered active user can request password reset
unknown email receives generic response
forgot password endpoint is rate limited
reset email contains valid reset link
reset token expires
invalid reset token rejected
used reset token cannot be reused
password confirmation must match
password policy is enforced
password is hashed
successful reset invalidates token
successful reset invalidates old sessions
inactive account remains inactive after reset
reset action creates audit log
```

---

## 38. Automated Tests — Admin Reset

Tambahkan:

```text
superadmin can reset target user password
superadmin must re-authenticate before administrative reset
owner cannot directly reset another user password
admin cannot directly reset another user password
employee cannot directly reset another user password
administrative reset invalidates target sessions
administrative reset creates audit log
administrative reset does not reveal old password
```

---

## 39. User Enumeration Test

Pastikan public response untuk:

```text
registered@example.com
```

dan:

```text
unknown@example.com
```

sama-sama menggunakan generic response dan tidak secara eksplisit membocorkan keberadaan akun.

---

## 40. Domain-Agnostic Audit

Search source project untuk:

```text
beauty.selon.my.id
selon.my.id
localhost
127.0.0.1
```

Pastikan reset password tidak mempunyai hardcoded production hostname.

Development reference di docs/tests boleh ada jika memang konteksnya development dan bukan runtime production URL.

---

## 41. Database Safety

DILARANG:

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

Jika password-reset token table/migration diperlukan, buat migration NON-DESTRUCTIVE.

Kemudian gunakan:

```bash
php artisan migrate
```

Jangan recreate users table.

---

## 42. No Dummy Data

Jangan membuat dummy user/employee/email pada development database utama.

Automated tests wajib menggunakan test database terpisah.

---

## 43. Regression

Pastikan perubahan tidak merusak:

- Login
- Logout
- Role Management Sprint 18.5
- Superadmin
- Owner
- Admin
- Employee Portal
- Attendance
- Shift
- Scheduling
- GPS
- Selfie
- Leave
- Overtime
- Reports
- Notifications
- Backup/Restore
- Branding
- PWA

---

## 44. Documentation Update

Update minimal:

```text
docs/PROJECT_CONTEXT.md
docs/ARCHITECTURE.md
docs/RULES.md
docs/PRODUCTION_PREFLIGHT.md
```

Tambahkan:

```text
Password Recovery = Email Reset Link
SMTP account required for production recovery email
```

Jangan menulis credential nyata.

---

## 45. RULES.md Permanent Rules

Tambahkan:

### Password Recovery
Password recovery menggunakan secure email reset link.

### No User Enumeration
Forgot-password public response tidak boleh membocorkan apakah user terdaftar.

### No Plaintext Secrets
Password, reset token, dan SMTP password tidak boleh disimpan/log plaintext.

### Session Revocation
Successful password reset harus mencabut session lama target user.

### Domain Agnostic
Reset URL tidak boleh hardcode hostname production.

---

## 46. Production Preflight Checklist

Tambahkan:

```text
[ ] MAIL_MAILER configured
[ ] MAIL_HOST configured
[ ] MAIL_PORT configured
[ ] MAIL_USERNAME configured
[ ] MAIL_PASSWORD configured
[ ] MAIL_ENCRYPTION configured
[ ] MAIL_FROM_ADDRESS configured
[ ] MAIL_FROM_NAME configured
[ ] APP_URL configured
[ ] HTTPS active
[ ] test email delivered
[ ] forgot password email delivered
[ ] reset link opens correct production domain
[ ] reset password succeeds
[ ] old session invalidated
```

---

## 47. Final Validation

Setelah implementasi jalankan:

```bash
php artisan migrate
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pertahankan seluruh data existing.

---

## 48. Final Backup

Setelah Sprint 18.6 PASS:

buat **Full Backup** baru menggunakan module Sprint 17.5.

Validate:

- status completed;
- checksum valid;
- database included;
- private files included sesuai full backup design.

Backup ini menjadi recovery point terakhir sebelum deployment production.

---

# ACCEPTANCE CRITERIA

SPRINT_18.6 hanya PASS jika:

## Forgot Password
- [ ] Link Lupa Password tersedia di Login
- [ ] Forgot Password page tersedia
- [ ] Email reset dapat dikirim
- [ ] Response tidak membocorkan user existence
- [ ] Rate limiting bekerja
- [ ] Reset token secure
- [ ] Token mempunyai expiration
- [ ] Token single-use
- [ ] Expired/invalid token ditolak

## Reset Password
- [ ] Password baru dapat disimpan
- [ ] Confirmation wajib cocok
- [ ] Existing password policy digunakan
- [ ] Password hashed
- [ ] Old session di-invalidasi
- [ ] User harus login ulang
- [ ] Audit Log tercatat

## Superadmin Recovery
- [ ] Superadmin dapat melakukan fallback reset
- [ ] Superadmin wajib re-authenticate
- [ ] Owner tidak dapat direct reset
- [ ] Admin tidak dapat direct reset
- [ ] Employee tidak dapat direct reset
- [ ] Target session invalidated
- [ ] Password lama tidak pernah terlihat

## Security
- [ ] Tidak ada plaintext password
- [ ] Tidak ada plaintext reset token di log
- [ ] Tidak ada SMTP credential di repository
- [ ] CSRF aktif
- [ ] No open redirect
- [ ] No user enumeration
- [ ] Inactive account tetap inactive
- [ ] Sensitive reset pages tidak dicache PWA

## Shared Hosting
- [ ] Tidak membutuhkan Redis
- [ ] Tidak membutuhkan Supervisor
- [ ] Tidak membutuhkan PM2
- [ ] Tidak membutuhkan WebSocket
- [ ] Tidak membutuhkan runtime Node server
- [ ] SMTP biasa dapat digunakan
- [ ] Reset email tidak bergantung daemon queue

## Domain Agnostic
- [ ] Tidak ada hardcoded production hostname
- [ ] Reset URL generated dari framework/config
- [ ] APP_URL dapat diganti tanpa source code change

## Regression
- [ ] Login PASS
- [ ] Role Management PASS
- [ ] Attendance PASS
- [ ] Scheduling PASS
- [ ] GPS/Selfie PASS
- [ ] Leave/Overtime PASS
- [ ] Reports PASS
- [ ] Backup/Restore PASS
- [ ] Branding/PWA PASS
- [ ] Existing data tetap utuh
- [ ] Automated tests PASS
- [ ] npm build PASS
- [ ] Cache validation PASS
- [ ] Final Full Backup VALID

---

# OUTPUT WAJIB SETELAH SELESAI

Berikan Completion Report:

1. Existing password recovery condition sebelum perubahan.
2. Routes/controller/service yang ditambahkan atau diperbaiki.
3. Final forgot password flow.
4. Reset token mechanism.
5. Token expiration.
6. Rate limiting.
7. User enumeration protection.
8. Session invalidation behavior.
9. Inactive account behavior.
10. Superadmin fallback reset behavior.
11. Role/policy untuk administrative reset.
12. Audit Log events.
13. Email template/branding implementation.
14. Domain-agnostic URL validation.
15. PWA cache security result.
16. SMTP configuration variables yang diperlukan.
17. Migration yang dibuat.
18. Automated test result.
19. `npm run build` result.
20. Cache validation result.
21. Existing database persistence result.
22. Final Full Backup UUID/checksum.
23. Known limitations.

**JANGAN mengerjakan SPRINT_19.**

Tunggu instruksi deployment berikutnya.
