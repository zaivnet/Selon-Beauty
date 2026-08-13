# UI / UX SPECIFICATION

## 1. Visual Direction

Brand: SELON BEAUTY

Arah:
- modern;
- premium;
- feminin tanpa terlihat kekanak-kanakan;
- clean;
- colorful tetapi tetap profesional;
- card tidak terasa polos;
- banyak white space;
- icon harus mudah dibedakan.

Jangan membuat semua icon memakai bentuk visual yang terlalu serupa.

## 2. Design Tokens

Gunakan CSS variables / Tailwind theme agar branding mudah diganti.

Contoh kelompok warna:
- primary;
- secondary;
- accent;
- success;
- warning;
- danger;
- info;
- neutral.

Hindari hardcode warna berbeda-beda di setiap component.

## 3. Desktop Admin Layout

```text
┌──────────── Sidebar ────────────┬──────────────────────────┐
│ SELON BEAUTY                    │ Topbar                   │
│                                 ├──────────────────────────┤
│ Dashboard                       │ Page content             │
│ Karyawan                        │                          │
│ Jadwal                          │                          │
│ Absensi                         │                          │
│ Pengajuan                       │                          │
│ Lembur                          │                          │
│ Laporan                         │                          │
│ Pengaturan                      │                          │
└─────────────────────────────────┴──────────────────────────┘
```

Sidebar:
- collapsible;
- active item jelas;
- icon distinct;
- tooltip ketika collapsed;
- tidak boleh overlap.

User menu:
- avatar;
- nama;
- role;
- profile;
- logout.
Dropdown harus memiliki alignment, spacing, shadow, z-index dan mobile behavior yang rapi.

## 4. Employee Mobile Layout

Mobile harus terasa seperti aplikasi, bukan desktop yang diperkecil.

```text
┌─────────────────────────┐
│ SELON BEAUTY      Avatar│
│ Selamat pagi, Ayu       │
│ Selasa, 11 Agustus      │
│                         │
│ [ Shift hari ini ]      │
│ 09:00 → 17:00           │
│                         │
│ Status lokasi ✓         │
│                         │
│ [   ABSEN MASUK   ]     │
│                         │
│ Ringkasan hari ini      │
│                         │
├─────────────────────────┤
│ Home Jadwal Absen Izin Profil
└─────────────────────────┘
```

Bottom navigation:
- fixed;
- safe-area aware;
- maksimal 5 menu utama;
- icon + label;
- active state kuat;
- jangan menutupi content.

## 5. Attendance Screen

Urutan UX:
1. Shift.
2. Current time.
3. Location status.
4. Camera/selfie status.
5. CTA.
6. Result.

States:
- loading location;
- permission denied;
- low accuracy;
- outside radius;
- ready;
- submitting;
- success;
- failed.

Jangan mengaktifkan CTA sebelum syarat minimum valid.

## 6. Camera UX

- default ke front camera pada mobile bila tersedia;
- preview jelas;
- tombol capture besar;
- retake;
- confirm;
- tampilkan instruksi singkat;
- jangan upload otomatis sebelum user confirm kecuali requirement berubah.

## 7. Dashboard Admin Cards

Card tidak boleh hanya kotak putih polos.

Setiap KPI card dapat memakai:
- subtle tinted background;
- icon badge;
- small trend/context;
- hierarchy typography;
- decorative gradient yang ringan.

Contoh:
- Hadir: success semantic.
- Terlambat: warning.
- Tidak hadir: danger.
- Pengajuan: info/accent.

Tetap jaga accessibility dan jangan mengandalkan warna semata.

## 8. Data Tables

Desktop:
- search;
- filter;
- sort bila perlu;
- pagination;
- sticky header bila membantu;
- row actions konsisten.

Mobile:
- jangan memaksa tabel lebar.
- ubah ke stacked cards/list.
- aksi utama tetap mudah dijangkau.

## 9. Calendar Schedule

Desktop:
- week/month view;
- employee rows;
- shift badges;
- quick edit.

Mobile:
- date selector;
- list per hari;
- hindari grid 7 kolom kecil.

## 10. Forms

- label selalu terlihat;
- helper text;
- validation dekat field;
- required state jelas;
- destructive action mempunyai confirmation;
- success message tidak menghapus konteks user.

## 11. Empty States

Semua modul harus memiliki empty state yang nyata.

Contoh:
"Belum ada karyawan. Tambahkan karyawan pertama untuk mulai menyusun jadwal."

Jangan menggunakan dummy data untuk membuat layar terlihat penuh.

## 12. Loading

Gunakan:
- skeleton untuk page/card;
- inline spinner untuk action;
- disabled state saat submit.

Hindari full-screen loading untuk aksi kecil.

## 13. Responsive Breakpoints

Pastikan secara manual:
- 360px;
- 390px;
- 430px;
- 768px;
- 1024px;
- 1366px;
- 1440px.

Tidak boleh ada horizontal overflow yang tidak disengaja.

## 14. PWA Feel

Saat standalone:
- tidak mengandalkan browser back button;
- topbar/mobile navigation jelas;
- safe area iOS/Android;
- no awkward browser-sized modal;
- touch feedback;
- no hover-only controls.

## 15. Iconography

Gunakan satu library konsisten seperti Lucide.
Pilih icon berdasarkan makna:
- Users untuk karyawan;
- CalendarDays untuk jadwal;
- MapPin/ScanLine untuk absensi;
- ClipboardCheck untuk pengajuan;
- Clock3 untuk lembur;
- ChartNoAxesCombined untuk laporan;
- Settings untuk pengaturan.

Jangan memilih beberapa icon yang hampir sama hanya karena terlihat seragam.
