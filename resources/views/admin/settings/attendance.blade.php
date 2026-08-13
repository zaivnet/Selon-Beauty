@extends('layouts.admin')

@section('title', 'Pengaturan Lokasi & Absensi')
@section('page-title', 'Pengaturan Lokasi Toko & Absensi')

@section('content')
<div class="space-y-6">

    <!-- Settings Sub-Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
        <a href="{{ route('admin.settings.branding.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            🎨 Profil & Branding
        </a>
        <a href="{{ route('admin.settings.attendance') }}" class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition-colors bg-slate-900 text-white shadow-xs">
            📍 Pengaturan Absensi
        </a>
        <a href="{{ route('admin.settings.backups.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            💾 Backup & Restore
        </a>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- 1. Active Primary Location Overview Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl border border-slate-700 shadow-md p-6 text-white space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center border border-rose-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-white">Lokasi Absensi Utama SELON BEAUTY</h3>
                    <p class="text-xs text-slate-300">Geofencing server-side aktif untuk pembatasan radius presensi.</p>
                </div>
            </div>
            <div>
                @if($activeLocation)
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold text-xs rounded-full">
                        ✓ Status Aktif
                    </span>
                @else
                    <span class="px-3 py-1 bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold text-xs rounded-full">
                        ! Belum Ada Lokasi Aktif
                    </span>
                @endif
            </div>
        </div>

        @if($activeLocation)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 text-xs border-t border-slate-700/60">
                <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Nama Toko</span>
                    <span class="font-bold text-white mt-0.5 block truncate">{{ $activeLocation->name }}</span>
                </div>
                <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Koordinat (Lat, Lon)</span>
                    <span class="font-mono font-bold text-rose-300 mt-0.5 block truncate">{{ $activeLocation->latitude }}, {{ $activeLocation->longitude }}</span>
                </div>
                <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Radius Absensi</span>
                    <span class="font-bold text-white mt-0.5 block">{{ $activeLocation->radius_meters }} Meter</span>
                </div>
                <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Maksimal Akurasi GPS</span>
                    <span class="font-bold text-white mt-0.5 block">{{ $activeLocation->max_accuracy_meters }} Meter</span>
                </div>
            </div>
        @else
            <div class="p-4 bg-slate-800/60 rounded-xl border border-dashed border-slate-700 text-xs text-slate-300">
                Belum ada lokasi toko aktif. Silakan lengkapi formulir di bawah ini untuk mengonfigurasi lokasi utama toko.
            </div>
        @endif
    </div>

    <!-- 2. Main Grid: Form Tambah/Edit Lokasi & Simulator Jarak -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Form Tambah Lokasi Toko -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah / Atur Lokasi Toko Baru</span>
            </h3>

            <form action="{{ route('admin.settings.locations.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lokasi / Cabang *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', 'SELON BEAUTY Utama') }}" required placeholder="Contoh: SELON BEAUTY Grand Mall" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('name')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap (Opsional)</label>
                    <textarea name="address" id="address" rows="2" placeholder="Jl. Raya Utama No. 123, Jakarta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">{{ old('address') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="latitude" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Latitude * (-90 s.d. 90)</label>
                        <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $activeLocation?->latitude ?? -6.175392) }}" required placeholder="-6.175392" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        @error('latitude')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Longitude * (-180 s.d. 180)</label>
                        <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $activeLocation?->longitude ?? 106.827153) }}" required placeholder="106.827153" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        @error('longitude')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tombol "Lokasi Saya" Browser Geolocation & Status Akurasi -->
                <div class="space-y-2">
                    <button type="button" id="btn-get-my-location" onclick="getMyStoreLocation()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="icon-location" class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <svg id="icon-spinner" class="w-4 h-4 text-rose-600 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                        <span id="btn-location-label">📍 Gunakan Lokasi Saya</span>
                    </button>

                    <div id="location-status-container" class="hidden p-3 rounded-xl text-xs space-y-1"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="radius_meters" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Radius Absensi (Meter) *</label>
                        <input type="number" name="radius_meters" id="radius_meters" value="{{ old('radius_meters', $activeLocation?->radius_meters ?? 50) }}" required min="1" max="10000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        @error('radius_meters')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_accuracy_meters" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Maks Akurasi GPS (Meter) *</label>
                        <input type="number" name="max_accuracy_meters" id="max_accuracy_meters" value="{{ old('max_accuracy_meters', $activeLocation?->max_accuracy_meters ?? 100) }}" required min="1" max="1000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        @error('max_accuracy_meters')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                    <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">Jadikan Lokasi Absensi Utama</label>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                    Simpan Lokasi Absensi
                </button>
            </form>
        </div>

        <!-- Simulator & Pengecekan Jarak Haversine -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>Simulator Evaluasi Geofence (Haversine Server-Side)</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Uji perhitungan jarak koordinat karyawan secara instan terhadap lokasi toko aktif.</p>
            </div>

            @if(! $activeLocation)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs">
                    Simulasi membutuhkan lokasi toko aktif. Silakan tambahkan lokasi toko terlebih dahulu.
                </div>
            @else
                <form action="{{ route('admin.settings.attendance') }}" method="GET" class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600">Lat Karyawan</label>
                            <input type="number" step="any" name="test_lat" value="{{ request('test_lat', $activeLocation->latitude) }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-mono bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600">Lon Karyawan</label>
                            <input type="number" step="any" name="test_lon" value="{{ request('test_lon', $activeLocation->longitude) }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-mono bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600">Akurasi GPS (m)</label>
                            <input type="number" step="any" name="test_accuracy" value="{{ request('test_accuracy', 15) }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer">
                        Hitung Jarak & Evaluasi Status Geofence
                    </button>
                </form>

                @if($testResult)
                    <div class="p-4 rounded-xl border {{ $testResult['is_valid'] ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' }} text-xs space-y-2">
                        <div class="flex items-center justify-between font-bold">
                            <span>Hasil Evaluasi Server-Side:</span>
                            @if($testResult['is_valid'])
                                <span class="px-2 py-0.5 bg-emerald-600 text-white rounded-full text-[10px]">VALID / DIIDZINKAN</span>
                            @else
                                <span class="px-2 py-0.5 bg-rose-600 text-white rounded-full text-[10px]">DITOLAK / INVALID</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>Jarak Terhitung: <strong class="font-mono">{{ $testResult['distance_meters'] }} Meter</strong></div>
                            <div>Batas Radius Toko: <strong>{{ $activeLocation->radius_meters }} Meter</strong></div>
                            <div>Akurasi Terdeteksi: <strong>{{ $testResult['test_accuracy'] }} Meter</strong></div>
                            <div>Maks Akurasi Toko: <strong>{{ $activeLocation->max_accuracy_meters }} Meter</strong></div>
                        </div>
                        @if($testResult['error_message'])
                            <p class="font-semibold text-rose-700 pt-1 border-t border-rose-200/60">
                                Peringatan: {{ $testResult['error_message'] }}
                            </p>
                        @endif
                    </div>
                @endif
            @endif
        </div>

    </div>

    <!-- 3. Daftar Semua Lokasi Toko (Multi-Location Ready) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Daftar Lokasi Absensi Terdaftar (Multi-Location)</h3>

        @if($locations->isEmpty())
            <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <p class="text-xs font-semibold text-slate-700">Belum ada lokasi absensi terdaftar.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider bg-slate-50/50">
                            <th class="p-3">Nama Lokasi</th>
                            <th class="p-3">Latitude / Longitude</th>
                            <th class="p-3">Radius</th>
                            <th class="p-3">Maks Akurasi</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($locations as $loc)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ $loc->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $loc->address ?: '-' }}</div>
                                </td>
                                <td class="p-3 font-mono text-slate-700">{{ $loc->latitude }}, {{ $loc->longitude }}</td>
                                <td class="p-3 font-semibold text-slate-800">{{ $loc->radius_meters }}m</td>
                                <td class="p-3 font-semibold text-slate-800">{{ $loc->max_accuracy_meters }}m</td>
                                <td class="p-3">
                                    @if($loc->is_active)
                                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] rounded-full">Utama & Aktif</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200 font-bold text-[10px] rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-2">
                                    <form action="{{ route('admin.settings.locations.toggle-status', $loc) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-slate-700 hover:text-slate-900 underline cursor-pointer">
                                            {{ $loc->is_active ? 'Nonaktifkan' : 'Jadikan Utama' }}
                                        </button>
                                    </form>

                                    @if(! $loc->is_active)
                                        <form action="{{ route('admin.settings.locations.destroy', $loc) }}" method="POST" class="inline" onsubmit="return confirm('Hapus lokasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- 4. Form Pengaturan Global Absensi -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Pengaturan Parameter Absensi Toko</h3>

        <form action="{{ route('admin.settings.attendance.update') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="timezone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Timezone *</label>
                    <select name="timezone" id="timezone" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        <option value="Asia/Jakarta" {{ $settings['timezone'] === 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                        <option value="Asia/Makassar" {{ $settings['timezone'] === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                        <option value="Asia/Jayapura" {{ $settings['timezone'] === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 sm:pt-6">
                    <input type="checkbox" name="require_checkout_geofence" id="require_checkout_geofence" value="1" {{ $settings['require_checkout_geofence'] ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                    <label for="require_checkout_geofence" class="text-xs font-bold text-slate-700 cursor-pointer">Wajib Geofence Saat Check-out</label>
                </div>

                <div class="flex items-center gap-2 sm:pt-6">
                    <input type="checkbox" name="require_selfie" id="require_selfie" value="1" {{ $settings['require_selfie'] ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                    <label for="require_selfie" class="text-xs font-bold text-slate-700 cursor-pointer">Wajib Foto Selfie Absensi</label>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                    Simpan Pengaturan Absensi
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Vanilla JS Geolocation Handler -->
<script>
function getMyStoreLocation() {
    const btn = document.getElementById('btn-get-my-location');
    const label = document.getElementById('btn-location-label');
    const iconLocation = document.getElementById('icon-location');
    const iconSpinner = document.getElementById('icon-spinner');
    const statusContainer = document.getElementById('location-status-container');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');

    if (!navigator.geolocation) {
        statusContainer.className = 'p-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 text-xs font-semibold';
        statusContainer.innerHTML = 'Browser ini tidak mendukung fitur lokasi.';
        statusContainer.classList.remove('hidden');
        return;
    }

    // Set Loading state
    btn.disabled = true;
    label.innerText = '⟳ Mencari Lokasi...';
    iconLocation.classList.add('hidden');
    iconSpinner.classList.remove('hidden');
    statusContainer.classList.add('hidden');

    const options = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = Math.round(position.coords.accuracy);

            // Auto fill inputs without saving to DB
            latInput.value = lat;
            lonInput.value = lon;

            // Semantic accuracy indicators
            let badgeClass = '';
            let badgeTitle = '';
            let isLowAccuracy = false;

            if (accuracy <= 20) {
                badgeClass = 'bg-emerald-50 border-emerald-200 text-emerald-900';
                badgeTitle = '✓ Akurasi Sangat Baik';
            } else if (accuracy <= 50) {
                badgeClass = 'bg-emerald-50 border-emerald-200 text-emerald-900';
                badgeTitle = '✓ Akurasi Baik';
            } else if (accuracy <= 100) {
                badgeClass = 'bg-amber-50 border-amber-200 text-amber-900';
                badgeTitle = '⚠ Akurasi Sedang';
            } else {
                badgeClass = 'bg-amber-50 border-amber-200 text-amber-900';
                badgeTitle = '⚠ Akurasi GPS Rendah';
                isLowAccuracy = true;
            }

            statusContainer.className = 'p-3 rounded-xl border ' + badgeClass + ' text-xs space-y-1';
            statusContainer.innerHTML = `
                <div class="font-bold flex items-center justify-between">
                    <span>📍 Lokasi Berhasil Ditemukan</span>
                    <span class="px-2 py-0.5 bg-white/80 rounded-md shadow-2xs text-[11px] font-extrabold">${badgeTitle}</span>
                </div>
                <div class="text-[11px]">Akurasi GPS: <strong>± ${accuracy} meter</strong></div>
                ${isLowAccuracy ? '<div class="text-[11px] font-semibold text-amber-800 mt-1">Disarankan mencoba kembali di area terbuka untuk mendapatkan akurasi yang lebih presisi.</div>' : ''}
            `;
            statusContainer.classList.remove('hidden');

            // Reset button to Retry state
            btn.disabled = false;
            label.innerText = '⟳ Perbarui Lokasi Saya';
            iconLocation.classList.remove('hidden');
            iconSpinner.classList.add('hidden');
        },
        function(error) {
            let errorMsg = 'Terjadi kesalahan saat mengambil lokasi.';

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = 'Akses lokasi ditolak. Izinkan akses lokasi pada browser untuk menggunakan fitur Lokasi Saya.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = 'Lokasi tidak dapat ditemukan. Pastikan GPS/lokasi perangkat aktif.';
                    break;
                case error.TIMEOUT:
                    errorMsg = 'Pencarian lokasi terlalu lama. Silakan coba kembali.';
                    break;
            }

            statusContainer.className = 'p-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 text-xs font-semibold';
            statusContainer.innerHTML = errorMsg;
            statusContainer.classList.remove('hidden');

            // Reset button state
            btn.disabled = false;
            label.innerText = '📍 Gunakan Lokasi Saya';
            iconLocation.classList.remove('hidden');
            iconSpinner.classList.add('hidden');
        },
        options
    );
}
</script>
@endsection
