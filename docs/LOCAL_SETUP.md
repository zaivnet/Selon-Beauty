# LOCAL SETUP — SELON BEAUTY Attendance

Panduan langkah-langkah instalasi dan konfigurasi lokal (*clean local install*):

1. **Clone Repository & Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Konfigurasi Environment (`.env`)**:
   Salin `.env.example` ke `.env` dan atur variabel koneksi database:
   ```env
   APP_NAME="SELON BEAUTY"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_TIMEZONE=Asia/Jakarta
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=selon_beauty_attendance
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Generate Application Key & Build Assets**:
   ```bash
   php artisan key:generate
   npm run build
   ```

4. **Jalankan Database Migration & Standard Seeders**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=DatabaseSeeder
   ```
   > [!NOTE]
   > Migration ini membuat struktur tabel bersih **tanpa dummy users** dan **tanpa default password**. `DatabaseSeeder` hanya mengisikan variabel `app_settings` default.

5. **Jalankan Aplikasi Development Server**:
   ```bash
   php artisan serve
   ```

6. **Inisialisasi First-Run Setup**:
   - Buka browser ke: `http://127.0.0.1:8000/` atau `http://127.0.0.1:8000/setup`.
   - Aplikasi otomatis mengarahkan ke form **First-Run Superadmin Setup** (`/setup`).
   - Isi **Nama Lengkap**, **Email**, **Password**, dan **Konfirmasi Password** Superadmin utama.
   - Klik **Inisialisasi & Buat Superadmin**.
   - Setelah sukses, sistem mengunci endpoint `/setup` dan mengarahkan ke `/login`.

7. **Login Superadmin**:
   - Login menggunakan Email dan Password Superadmin yang baru dibuat.
   - Sistem mengarahkan ke `/admin/dashboard`.
