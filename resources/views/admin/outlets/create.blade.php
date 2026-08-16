@extends('layouts.admin')

@section('title', 'Tambah Outlet Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.outlets.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar Outlet
            </a>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Tambah Outlet & Cabang Baru</h1>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('admin.outlets.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Nama & Kode Outlet -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Nama Outlet / Cabang <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Contoh: Selon Beauty Cabang Kemang" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">
                    @error('name')
                        <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Kode Outlet <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" placeholder="CBG02" required uppercase
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none uppercase transition-all">
                    @error('code')
                        <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Alamat Fisik -->
            <div>
                <label for="address" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    Alamat Fisik Outlet
                </label>
                <textarea name="address" id="address" rows="3" placeholder="Jl. Kemang Raya No. 12, Jakarta Selatan"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">{{ old('address') }}</textarea>
                @error('address')
                    <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-slate-200/80 dark:border-slate-800">

            <!-- Geofence GPS Coordinates -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Koordinat Geofence Absensi Toko</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tentukan titik pusat GPS lokasi outlet fisik untuk validasi presensi karyawan.</p>
                    </div>
                    <button type="button" id="btn-get-location" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-all cursor-pointer shrink-0">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Gunakan Lokasi Saya Saat Ini</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="latitude" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Latitude GPS <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.0000001" name="latitude" id="latitude" value="{{ old('latitude', '-6.2000000') }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">
                        @error('latitude')
                            <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Longitude GPS <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.0000001" name="longitude" id="longitude" value="{{ old('longitude', '106.8166660') }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">
                        @error('longitude')
                            <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Radius & Maximum Accuracy -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="radius_meters" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Radius Presensi Terizin (Meter) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="radius_meters" id="radius_meters" value="{{ old('radius_meters', 100) }}" min="1" max="50000" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Jarak maksimum karyawan dari koordinat outlet saat check-in (default: 100m).</p>
                    @error('radius_meters')
                        <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_accuracy_meters" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                        Maksimal Akurasi GPS (Meter)
                    </label>
                    <input type="number" name="max_accuracy_meters" id="max_accuracy_meters" value="{{ old('max_accuracy_meters', 100) }}" min="1" max="500"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Batas toleransi akurasi perangkat HP karyawan (default: ±100m).</p>
                    @error('max_accuracy_meters')
                        <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                <label for="is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                    Aktifkan Outlet Ini Sekarang
                </label>
            </div>

            <!-- Form Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <a href="{{ route('admin.outlets.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-all cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-500/25 transition-all cursor-pointer">
                    Simpan Outlet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnGetLocation = document.getElementById('btn-get-location');
    const inputLat = document.getElementById('latitude');
    const inputLng = document.getElementById('longitude');

    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung Geolocation.');
                return;
            }

            btnGetLocation.disabled = true;
            btnGetLocation.innerHTML = '<span>Mengambil Lokasi...</span>';

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    inputLat.value = position.coords.latitude.toFixed(7);
                    inputLng.value = position.coords.longitude.toFixed(7);
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400">✓ Lokasi Berhasil Diambil</span>';
                    setTimeout(() => {
                        btnGetLocation.innerHTML = '<span>Gunakan Lokasi Saya Saat Ini</span>';
                    }, 3000);
                },
                function(error) {
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<span>Gunakan Lokasi Saya Saat Ini</span>';
                    alert('Gagal mengambil lokasi: ' + error.message);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }
});
</script>
@endsection
