<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>First-Run Setup Superadmin - {{ $branding['app_name'] ?? 'SELON BEAUTY' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="theme-color" content="{{ $branding['pwa_theme_color'] ?? '#E11D48' }}">
    <link rel="icon" type="{{ $branding['favicon_mime_type'] ?? 'image/x-icon' }}" href="{{ $branding['favicon_url'] ?? asset('favicon.ico') }}">
    <style>
        :root {
            --brand-primary: {{ $branding['brand_primary'] ?? '#E11D48' }};
            --brand-accent: {{ $branding['brand_accent'] ?? '#F43F5E' }};
        }
    </style>
    @include('partials.theme_bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex items-center justify-center p-4 relative overflow-x-hidden transition-colors">

    <!-- Ambient Background Accent Blurs -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-rose-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-pink-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md space-y-6 relative z-10">

        <!-- Brand & Title Header -->
        <div class="text-center space-y-3">
            @if(!empty($branding['app_logo_url']))
                <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}" class="w-14 h-14 mx-auto object-contain rounded-2xl shadow-xl shadow-rose-950/50">
            @else
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-rose-600 via-pink-600 to-rose-500 flex items-center justify-center font-black text-white text-xl shadow-xl shadow-rose-950/50">
                    {{ strtoupper(substr($branding['app_name'] ?? 'SB', 0, 2)) }}
                </div>
            @endif

            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ $branding['app_name'] ?? 'SELON BEAUTY' }}</h1>
                <p class="text-xs font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mt-0.5">First-Run System Setup</p>
            </div>
        </div>

        <!-- Setup Form Card -->
        <div class="bg-white dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200 dark:border-slate-700/80 shadow-2xl p-6 sm:p-8 space-y-6 transition-colors">
            
            <div class="border-b border-slate-200 dark:border-slate-700/80 pb-4">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Inisialisasi Superadmin Utama</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    Sistem belum memiliki akun pengelola. Buat akun Superadmin pertama untuk mengaktifkan seluruh portal aplikasi.
                </p>
            </div>

            <!-- Validation Errors & Flash Alerts -->
            @if(session('error'))
                <div class="p-4 bg-rose-950/60 border border-rose-700 text-rose-200 rounded-2xl text-xs font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('setup.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Application & Company Branding (Optional) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-2 border-b border-slate-200 dark:border-slate-700/50">
                    <div>
                        <label for="app_name" class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Nama Aplikasi (Opsional)</label>
                        <input type="text" 
                               name="app_name" 
                               id="app_name" 
                               value="{{ old('app_name', $branding['app_name'] ?? '') }}" 
                               placeholder="e.g. Attendance Portal" 
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label for="company_name" class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Nama Perusahaan (Opsional)</label>
                        <input type="text" 
                               name="company_name" 
                               id="company_name" 
                               value="{{ old('company_name', $branding['company_name'] ?? '') }}" 
                               placeholder="e.g. PT Perusahaan Utama" 
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap Superadmin *</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name') }}" 
                           required 
                           autofocus 
                           placeholder="Contoh: Super Administrator" 
                           class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    @error('name')
                        <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email Superadmin *</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email') }}" 
                           required 
                           placeholder="admin@selonbeauty.com" 
                           class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    @error('email')
                        <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password *</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required 
                           placeholder="Minimal 8 karakter" 
                           class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    @error('password')
                        <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Password *</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           required 
                           placeholder="Ulangi password baru" 
                           class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>

                <!-- Security Note -->
                <div class="p-3 bg-slate-100 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700/50 text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                    🔒 Role <strong class="text-rose-500 dark:text-rose-400">Superadmin</strong> dan status <strong class="text-emerald-600 dark:text-emerald-400">Aktif</strong> ditentukan secara otomatis oleh server. Akun ini tidak terikat dengan data karyawan operasional toko.
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-rose-950/50 transition-all cursor-pointer flex items-center justify-center gap-2">
                    <span>Inisialisasi & Buat Superadmin</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center font-medium">
            &copy; {{ date('Y') }} {{ $branding['app_name'] ?? 'SELON BEAUTY' }}. All rights reserved.
        </p>

    </div>

</body>
</html>
