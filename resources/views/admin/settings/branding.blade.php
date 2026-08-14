@extends('layouts.admin')

@section('title', 'Profil Aplikasi & Branding')
@section('page-title', 'Pengaturan Aplikasi')

@section('content')
<div class="space-y-6">

    <!-- Settings Sub-Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
        <a href="{{ route('admin.settings.branding.index') }}" class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition-colors bg-slate-900 text-white shadow-xs">
            🎨 Profil & Branding
        </a>
        <a href="{{ route('admin.settings.locations.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            📍 Pengaturan Absensi
        </a>
        <a href="{{ route('admin.settings.backups.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            💾 Backup & Restore
        </a>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Card 1: Identitas & Teks Aplikasi -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Identitas Visual & Teks Aplikasi</h3>
                <p class="text-xs text-slate-500">Sesuaikan nama brand, tagline, dan informasi dasar yang tampil di seluruh portal.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Aplikasi *</label>
                    <input type="text" name="app_name" value="{{ old('app_name', $brandingData['app_name']) }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 font-bold text-slate-900">
                    @error('app_name') <span class="text-rose-500 text-[11px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Singkat (Short Name PWA) *</label>
                    <input type="text" name="app_short_name" value="{{ old('app_short_name', $brandingData['app_short_name']) }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 font-bold text-slate-900">
                    @error('app_short_name') <span class="text-rose-500 text-[11px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Perusahaan / Instansi</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $brandingData['company_name']) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 font-medium text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tagline Aplikasi</label>
                    <input type="text" name="app_tagline" value="{{ old('app_tagline', $brandingData['app_tagline']) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 font-medium text-slate-900">
                </div>
            </div>
        </div>

        <!-- Card 2: Logo, Icon & Favicon Upload -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Media & Icon Brand</h3>
                <p class="text-xs text-slate-500">Unggah file gambar resmi untuk logo, icon PWA, dan favicon browser.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Logo -->
                <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-3">
                    <span class="text-xs font-extrabold text-slate-800 block">Logo Utama</span>
                    <div class="h-20 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-2 overflow-hidden">
                        @if($brandingData['app_logo_url'])
                            <img src="{{ $brandingData['app_logo_url'] }}" alt="Logo" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-[11px] font-extrabold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200">
                                {{ substr($brandingData['app_name'], 0, 4) }}
                            </span>
                        @endif
                    </div>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <span class="text-[10px] text-slate-400 block">Format: PNG, JPG, WEBP. Maks 2MB.</span>
                </div>

                <!-- Icon PWA -->
                <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-3">
                    <span class="text-xs font-extrabold text-slate-800 block">Icon Aplikasi (PWA)</span>
                    <div class="h-20 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-2 overflow-hidden">
                        <img src="{{ $brandingData['app_icon_url'] }}" alt="Icon PWA" class="w-14 h-14 object-cover rounded-xl shadow-xs">
                    </div>
                    <input type="file" name="icon" accept="image/png" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <span class="text-[10px] text-slate-400 block">Format: PNG Persegi. Min 192x192px.</span>
                </div>

                <!-- Favicon -->
                <div class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-3">
                    <span class="text-xs font-extrabold text-slate-800 block">Favicon Browser</span>
                    <div class="h-20 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-2 overflow-hidden">
                        <img src="{{ $brandingData['favicon_url'] }}" alt="Favicon" class="w-8 h-8 object-contain">
                    </div>
                    <input type="file" name="favicon" accept="image/png,image/x-icon" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <span class="text-[10px] text-slate-400 block">Format: ICO atau PNG.</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Skema Warna Branding -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Skema Warna Brand</h3>
                <p class="text-xs text-slate-500">Tentukan warna aksen dan tema visual PWA menggunakan kode Hex HTML (#HEX).</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Primary Brand Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $brandingData['brand_primary'] }}" onchange="document.getElementById('brand_primary_hex').value = this.value" class="w-10 h-10 rounded-xl border border-slate-300 cursor-pointer p-0.5">
                        <input type="text" id="brand_primary_hex" name="brand_primary" value="{{ old('brand_primary', $brandingData['brand_primary']) }}" required class="px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold w-32 uppercase">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Accent Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $brandingData['brand_accent'] }}" onchange="document.getElementById('brand_accent_hex').value = this.value" class="w-10 h-10 rounded-xl border border-slate-300 cursor-pointer p-0.5">
                        <input type="text" id="brand_accent_hex" name="brand_accent" value="{{ old('brand_accent', $brandingData['brand_accent']) }}" required class="px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold w-32 uppercase">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">PWA Theme Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $brandingData['pwa_theme_color'] }}" onchange="document.getElementById('pwa_theme_color_hex').value = this.value" class="w-10 h-10 rounded-xl border border-slate-300 cursor-pointer p-0.5">
                        <input type="text" id="pwa_theme_color_hex" name="pwa_theme_color" value="{{ old('pwa_theme_color', $brandingData['pwa_theme_color']) }}" required class="px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold w-32 uppercase">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                Simpan Perubahan Branding
            </button>
        </div>
    </form>
</div>
@endsection
