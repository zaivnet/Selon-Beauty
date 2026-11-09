<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Setup Awal · {{ $branding['company_name'] }} — {{ $branding['product_name'] }}</title>
    <meta name="theme-color" content="{{ $branding['pwa_theme_color'] ?? '#0F766E' }}">
    <link rel="icon" type="{{ $branding['favicon_mime_type'] ?? 'image/x-icon' }}" href="{{ $branding['favicon_url'] ?? asset('favicon.ico') }}">
    <style>
        :root {
            --brand-primary: {{ $branding['brand_primary'] ?? '#0F766E' }};
            --brand-accent: {{ $branding['brand_accent'] ?? '#0284C7' }};
        }
    </style>
    @include('partials.theme_bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex flex-col items-center justify-center p-4 py-8 relative overflow-x-hidden transition-colors selection:bg-teal-500 selection:text-white">

    <!-- Ambient Glow Accents -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-teal-500/10 dark:bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-500/10 dark:bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Top Navigation Utility / Theme Selector -->
    <header class="w-full max-w-lg flex items-center justify-between mb-4 px-1 relative z-10">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>{{ $branding['product_name'] }} Installer</span>
        </div>

        <!-- Alpine Theme Switcher -->
        <div x-data="{
            theme: localStorage.getItem('attendance-theme') || 'system',
            setTheme(val) {
                this.theme = val;
                if (typeof window.applyAttendanceTheme === 'function') {
                    window.applyAttendanceTheme(val);
                }
            }
        }" class="flex items-center gap-1 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200/80 dark:border-slate-800 p-1 rounded-xl shadow-xs">
            <button type="button"
                    @click="setTheme('light')"
                    :class="theme === 'light' ? 'bg-slate-100 dark:bg-slate-800 text-teal-600 dark:text-teal-400 font-bold shadow-xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="min-h-[32px] min-w-[32px] flex items-center justify-center rounded-lg text-xs transition-all cursor-pointer"
                    aria-label="Mode Terang"
                    title="Mode Terang">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            <button type="button"
                    @click="setTheme('dark')"
                    :class="theme === 'dark' ? 'bg-slate-100 dark:bg-slate-800 text-teal-600 dark:text-teal-400 font-bold shadow-xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="min-h-[32px] min-w-[32px] flex items-center justify-center rounded-lg text-xs transition-all cursor-pointer"
                    aria-label="Mode Gelap"
                    title="Mode Gelap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
            <button type="button"
                    @click="setTheme('system')"
                    :class="theme === 'system' ? 'bg-slate-100 dark:bg-slate-800 text-teal-600 dark:text-teal-400 font-bold shadow-xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="min-h-[32px] min-w-[32px] flex items-center justify-center rounded-lg text-xs transition-all cursor-pointer"
                    aria-label="Sesuai Sistem"
                    title="Sesuai Sistem">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </button>
        </div>
    </header>

    <!-- Main Card Container -->
    <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl shadow-slate-200/40 dark:shadow-slate-950/60 overflow-hidden transition-all relative z-10">

        <!-- Header Branding Banner -->
        <div class="bg-gradient-to-br from-teal-800 via-teal-900 to-slate-900 text-white p-7 text-center relative overflow-hidden border-b border-teal-700/30">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-teal-500/15 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10">
                @if(!empty($branding['app_logo_url']))
                    <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['company_name'] }}" class="w-16 h-16 object-contain rounded-2xl mx-auto mb-3 shadow-lg shadow-teal-950/40 ring-1 ring-white/20">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mx-auto mb-3 text-2xl font-black shadow-lg shadow-teal-950/40 border border-white/20 text-teal-300">
                        {{ strtoupper(substr($branding['company_name'] ?? 'AZ', 0, 2)) }}
                    </div>
                @endif
                <h1 class="text-2xl font-black tracking-tight">{{ $branding['company_name'] }}</h1>
                <p class="text-xs text-teal-100/90 mt-1 font-medium">Inisialisasi Sistem &bull; {{ $branding['product_name'] }}</p>

                <!-- Installation Progress Steps -->
                <div class="grid grid-cols-3 gap-2 mt-5 max-w-xs mx-auto">
                    <div class="flex flex-col items-center gap-1">
                        <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[11px] font-bold">✓</span>
                        <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider">Lingkungan</span>
                    </div>
                    <div class="flex flex-col items-center gap-1">
                        <span class="w-6 h-6 rounded-full bg-white text-teal-900 flex items-center justify-center text-[11px] font-bold ring-2 ring-teal-400">2</span>
                        <span class="text-[9px] font-bold text-white uppercase tracking-wider">Profil & Superadmin</span>
                    </div>
                    <div class="flex flex-col items-center gap-1 opacity-60">
                        <span class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center text-[11px] font-bold">3</span>
                        <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider">Selesai</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Card Body -->
        <div class="p-6 md:p-8 space-y-6">

            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Inisialisasi Superadmin Utama</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    Sistem belum memiliki akun pengelola. Buat akun Superadmin pertama untuk mengaktifkan seluruh portal aplikasi dan memulai konfigurasi operasional.
                </p>
            </div>

            <!-- Validation Errors & Flash Alerts -->
            @if(session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-xs">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('setup.store') }}" method="POST" class="space-y-4" onsubmit="var btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); var txt = btn.querySelector('.btn-text'); if(txt) txt.textContent = 'Memproses Inisialisasi...'; }">
                @csrf

                <!-- Section: Profil Organisasi (Opsional) -->
                <div class="bg-slate-50/70 dark:bg-slate-950/50 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Profil Organisasi (Opsional)</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="app_name" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Nama Aplikasi</label>
                            <input type="text"
                                   name="app_name"
                                   id="app_name"
                                   value="{{ old('app_name', $branding['app_name'] ?? '') }}"
                                   placeholder="Contoh: Portal Presensi"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                        </div>
                        <div>
                            <label for="company_name" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Nama Perusahaan / Bisnis</label>
                            <input type="text"
                                   name="company_name"
                                   id="company_name"
                                   value="{{ old('company_name', $branding['company_name'] ?? '') }}"
                                   placeholder="Contoh: PT Perusahaan Utama"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                        </div>
                    </div>
                </div>

                <!-- Section: Akun Superadmin -->
                <div class="space-y-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap Superadmin *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   required
                                   autofocus
                                   autocomplete="name"
                                   placeholder="Contoh: Super Administrator"
                                   class="w-full pl-11 pr-4 py-3 rounded-2xl bg-slate-50/70 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                        </div>
                        @error('name')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email Superadmin *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                            </div>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   placeholder="admin@example.com"
                                   class="w-full pl-11 pr-4 py-3 rounded-2xl bg-slate-50/70 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                        </div>
                        @error('email')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div x-data="{ show: false }">
                        <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <input :type="show ? 'text' : 'password'"
                                   name="password"
                                   id="password"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Minimal 8 karakter"
                                   class="w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50/70 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                            <button type="button"
                                    @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                    class="absolute inset-y-0 right-0 pr-3.5 pl-3 flex items-center min-w-[44px] min-h-[44px] justify-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none cursor-pointer transition-colors">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Konfirmasi Password -->
                    <div x-data="{ show: false }">
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Password *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <input :type="show ? 'text' : 'password'"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Ulangi password baru"
                                   class="w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50/70 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all shadow-xs">
                            <button type="button"
                                    @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                    class="absolute inset-y-0 right-0 pr-3.5 pl-3 flex items-center min-w-[44px] min-h-[44px] justify-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none cursor-pointer transition-colors">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Security Architecture Note -->
                <div class="p-3.5 bg-slate-100 dark:bg-slate-950/70 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 leading-normal flex items-start gap-2.5">
                    <span class="text-sm shrink-0">🔒</span>
                    <div>
                        Role <strong class="text-teal-700 dark:text-teal-400 font-bold">Superadmin</strong> dan status <strong class="text-emerald-700 dark:text-emerald-400 font-bold">Aktif</strong> ditentukan secara otomatis oleh server. Akun ini tidak terikat dengan data karyawan operasional.
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 hover:from-teal-800 hover:to-slate-950 text-white font-extrabold rounded-2xl text-sm shadow-lg shadow-teal-950/20 transition-all transform active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 border border-teal-600/30">
                    <span class="btn-text">Inisialisasi & Buat Superadmin</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            @include('partials.auth_footer')

        </div>
    </div>

</body>
</html>
