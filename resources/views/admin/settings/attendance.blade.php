@extends('layouts.admin')

@section('title', 'Pengaturan Absensi Global')
@section('page-title', 'Pengaturan Absensi Global')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Settings Sub-Navigation Tabs -->
    <x-settings-nav active="attendance" />

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Banner Informasi Single Source of Truth Outlet -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 dark:from-slate-900 dark:to-slate-950 rounded-2xl border border-slate-700 dark:border-slate-800 shadow-md p-6 text-white space-y-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center border border-rose-500/30 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002-2v-2a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 002 2"/></svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-white">Geofence Lokasi Dikelola Per-Outlet</h3>
                <p class="text-xs text-slate-300">Setiap karyawan terikat ke outlet tertentu dan divalidasi berdasarkan koordinat & radius geofence outlet tersebut.</p>
            </div>
        </div>
        @if(in_array(Auth::user()?->role, ['superadmin', 'owner'], true))
            <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between text-xs">
                <span class="text-slate-300">Untuk mengelola lokasi fisik, koordinat GPS, dan radius presensi outlet:</span>
                <a href="{{ route('admin.outlets.index') }}" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-colors shrink-0 ui-btn ui-btn-primary">
                    Kelola Outlet & Cabang &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- Form Pengaturan Global Absensi -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 sm:p-8 space-y-6 transition-colors">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Aturan & Parameter Absensi Global</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Aturan umum yang berlaku secara global untuk seluruh outlet dan cabang.</p>
        </div>

        <form action="{{ route('admin.settings.attendance.update') }}" method="POST" class="space-y-6">
            @csrf

            <div class="space-y-6">
                <!-- Mode Operasional Outlet (Superadmin / Owner Only) -->
                @if(in_array(Auth::user()?->role, ['superadmin', 'owner'], true))
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Mode Operasional Outlet <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                            <!-- Single Outlet Card -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ $settings['outlet_mode'] === 'single' ? 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 hover:border-slate-300 dark:hover:border-slate-700' }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                        🏢 Single Outlet
                                    </span>
                                    <input type="radio" name="outlet_mode" value="single" {{ $settings['outlet_mode'] === 'single' ? 'checked' : '' }} class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-700">
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Aplikasi menggunakan <strong>satu lokasi operasional utama</strong>. Seluruh filter dan menu transfer multi-cabang disederhanakan.
                                </p>
                            </label>

                            <!-- Multi Outlet Card -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ $settings['outlet_mode'] === 'multi' ? 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 hover:border-slate-300 dark:hover:border-slate-700' }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                        🌐 Multi Outlet (Pusat & Cabang)
                                    </span>
                                    <input type="radio" name="outlet_mode" value="multi" {{ $settings['outlet_mode'] === 'multi' ? 'checked' : '' }} class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-700">
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Aplikasi dapat <strong>mengelola beberapa cabang outlet</strong>, jadwal piket terpisah, rotasi transfer karyawan, dan hak akses admin per outlet.
                                </p>
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">
                            Catatan: Beralih ke Single Outlet memerlukan hanya ada 1 outlet aktif dan tidak ada karyawan/jadwal yang terikat ke cabang lain.
                        </p>
                    </div>

                    <hr class="border-slate-200/80 dark:border-slate-800">
                @endif

                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Zona Waktu Sistem (Timezone) <span class="text-rose-500">*</span>
                    </label>
                    <select name="timezone" id="timezone" required class="w-full max-w-md px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 outline-none ui-input ui-select">
                        <option value="Asia/Jakarta" {{ $settings['timezone'] === 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                        <option value="Asia/Makassar" {{ $settings['timezone'] === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                        <option value="Asia/Jayapura" {{ $settings['timezone'] === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Ditentukan untuk standar pencatatan stempel waktu server.</p>
                </div>

                <hr class="border-slate-200/80 dark:border-slate-800">

                <!-- Validasi Geofence Check-out -->
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="require_checkout_geofence" id="require_checkout_geofence" value="1" {{ $settings['require_checkout_geofence'] ? 'checked' : '' }} class="mt-0.5 w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500 cursor-pointer">
                    <div>
                        <label for="require_checkout_geofence" class="text-xs font-bold text-slate-900 dark:text-slate-100 cursor-pointer">
                            Wajib Validasi Geofence GPS Saat Check-out (Absen Keluar)
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Jika diaktifkan, karyawan wajib berada di radius geofence outlet saat melakukan absen keluar. Jika dinonaktifkan, koordinat tetap dicatat sebagai bukti tanpa memblokir check-out.
                        </p>
                    </div>
                </div>

                <!-- Validasi Foto Selfie -->
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="require_selfie" id="require_selfie" value="1" {{ $settings['require_selfie'] ? 'checked' : '' }} class="mt-0.5 w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500 cursor-pointer">
                    <div>
                        <label for="require_selfie" class="text-xs font-bold text-slate-900 dark:text-slate-100 cursor-pointer">
                            Wajib Foto Selfie Saat Absensi (Check-in & Check-out)
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Karyawan wajib mengunggah foto wajah secara langsung melalui kamera HP saat presensi.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-500/25 transition-all cursor-pointer ui-btn ui-btn-primary">
                    Simpan Pengaturan Absensi Global
                </button>
            </div>
        </form>
    </div>

    <!-- System Version Info Card -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $branding['company_name'] ?? config('app.name', 'Selon Beauty') }}</span>
            <span>&bull;</span>
            <span>Sistem Presensi & Operasional</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-slate-400">Rilis Aplikasi:</span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200 text-[11px]">
                Versi {{ config('app.version', '1.0.0') }}
            </span>
        </div>
    </div>

</div>
@endsection
