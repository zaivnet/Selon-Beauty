<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lupa Password · {{ $branding['company_name'] }} — {{ $branding['product_name'] }}</title>
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
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex flex-col items-center justify-center p-4 relative overflow-x-hidden transition-colors selection:bg-teal-500 selection:text-white">

    <!-- Ambient Glow Accents -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-teal-500/10 dark:bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-500/10 dark:bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Top Navigation Utility / Theme Selector -->
    <header class="w-full max-w-md flex items-center justify-between mb-4 px-1 relative z-10">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>{{ $branding['product_name'] }}</span>
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
    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl shadow-slate-200/40 dark:shadow-slate-950/60 overflow-hidden transition-all relative z-10">

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
                <p class="text-xs text-teal-100/90 mt-1 font-medium">Atur Ulang Password Akun</p>
            </div>
        </div>

        <!-- Form Card Body -->
        <div class="p-6 md:p-8 space-y-5">

            <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Lupa Password</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    Masukkan email yang terdaftar pada akun Anda. Kami akan mengirimkan link aman untuk mengatur ulang password.
                </p>
            </div>

            <!-- Generic Status Message -->
            @if(session('status'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 rounded-2xl text-xs font-semibold leading-relaxed flex items-start gap-2.5 shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 1118 0z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-xs">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4" onsubmit="var btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); var txt = btn.querySelector('.btn-text'); if(txt) txt.textContent = 'Mengirim...'; }">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email Terdaftar *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="email"
                               placeholder="nama@email.com"
                               class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all bg-slate-50/70 dark:bg-slate-950 dark:text-slate-100 dark:placeholder-slate-500 shadow-xs">
                    </div>
                    @error('email')
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 hover:from-teal-800 hover:to-slate-950 text-white font-extrabold rounded-2xl text-sm shadow-lg shadow-teal-950/20 transition-all transform active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 border border-teal-600/30">
                    <span class="btn-text">Kirim Link Reset</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            <div class="pt-2 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 font-bold transition-colors min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Halaman Login</span>
                </a>
            </div>

            @include('partials.auth_footer')

        </div>
    </div>

</body>
</html>
