@extends('layouts.admin')

@section('title', 'Tambah Shift Baru')
@section('page-title', 'Form Tambah Shift Kerja')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.shifts.index') }}" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 flex items-center gap-1">
            &larr; Kembali ke Daftar Shift
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Informasi Shift Kerja Baru</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tentukan jam kerja, batas toleransi keterlambatan, dan jendela waktu absensi.</p>
        </div>

        <form action="{{ route('admin.shifts.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nama Shift -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Shift *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Contoh: Shift Pagi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('name')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Shift -->
                <div>
                    <label for="code" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kode Shift Unik * (Kapital)</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required placeholder="PAGI / SIANG / NIGHT" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono font-bold uppercase focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('code')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jam Mulai -->
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jam Mulai Shift * (HH:MM)</label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time', '09:00') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('start_time')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jam Selesai -->
                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jam Selesai Shift * (HH:MM)</label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time', '17:00') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Jika jam selesai lebih awal dari jam mulai (misal 22:00–06:00), sistem otomatis menandai Lintas Tengah Malam (Cross-midnight).</p>
                    @error('end_time')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Grace Period / Toleransi Keterlambatan -->
                <div>
                    <label for="grace_period_minutes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Toleransi Keterlambatan (Menit) *</label>
                    <input type="number" name="grace_period_minutes" id="grace_period_minutes" value="{{ old('grace_period_minutes', 5) }}" required min="0" max="240" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Contoh: 5 menit (jam 09:05 masih Tepat Waktu, jam 09:06 Terlambat 1 menit).</p>
                    @error('grace_period_minutes')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Durasi Istirahat -->
                <div>
                    <label for="break_minutes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Durasi Istirahat (Menit) *</label>
                    <input type="number" name="break_minutes" id="break_minutes" value="{{ old('break_minutes', 60) }}" required min="0" max="480" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('break_minutes')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800">

            <!-- Parameter Jendela Waktu Presensi -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Jendela Waktu Presensi (Check-in / Check-out Window)</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="check_in_open_minutes_before" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400">Buka Check-in (Menit Sebelum)</label>
                        <input type="number" name="check_in_open_minutes_before" id="check_in_open_minutes_before" value="{{ old('check_in_open_minutes_before', 60) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Default: 60 m sebelum shift</span>
                    </div>

                    <div>
                        <label for="check_in_close_minutes_after" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400">Tutup Check-in (Menit Setelah)</label>
                        <input type="number" name="check_in_close_minutes_after" id="check_in_close_minutes_after" value="{{ old('check_in_close_minutes_after', 120) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Default: 120 m setelah shift</span>
                    </div>

                    <div>
                        <label for="check_out_open_minutes_before" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400">Buka Check-out (Menit Sebelum)</label>
                        <input type="number" name="check_out_open_minutes_before" id="check_out_open_minutes_before" value="{{ old('check_out_open_minutes_before', 60) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Default: 60 m sebelum pulang</span>
                    </div>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800">

            <!-- Parameter Automatic Shift Checkout -->
            <div class="space-y-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Automatic Shift Checkout</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sistem otomatis melakukan checkout jika karyawan lupa checkout setelah shift berakhir.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="auto_checkout_enabled" id="auto_checkout_enabled" value="1" {{ old('auto_checkout_enabled', '1') == '1' ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500">
                        <label for="auto_checkout_enabled" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Aktif</label>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200/80 dark:border-slate-700/80">
                    <label for="auto_checkout_grace_minutes" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400">Checkout Otomatis Setelah (Menit setelah shift berakhir)</label>
                    <div class="flex items-center gap-3 mt-1">
                        <input type="number" name="auto_checkout_grace_minutes" id="auto_checkout_grace_minutes" value="{{ old('auto_checkout_grace_minutes', 10) }}" required min="0" max="180" class="w-32 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Menit (Default: 10 menit. Nilai 0 = tepat saat shift selesai)</span>
                    </div>
                    @error('auto_checkout_grace_minutes')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500">
                <label for="is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Status Aktif Shift</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.shifts.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    Simpan Shift Kerja
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
