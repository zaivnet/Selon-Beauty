<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lupa Password - {{ $branding['app_name'] ?? 'SELON BEAUTY' }}</title>
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

        <!-- Brand Header -->
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
                <p class="text-xs font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mt-0.5">Atur Ulang Password</p>
            </div>
        </div>

        <!-- Forgot Password Card -->
        <div class="bg-white dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200 dark:border-slate-700/80 shadow-2xl p-6 sm:p-8 space-y-6 transition-colors">
            
            <div class="border-b border-slate-200 dark:border-slate-700/80 pb-4">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Lupa Password Akun</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    Masukkan email yang terdaftar pada akun Anda. Kami akan mengirimkan link aman untuk mengatur ulang password.
                </p>
            </div>

            <!-- Generic Status Message -->
            @if(session('status'))
                <div class="p-4 bg-emerald-950/60 border border-emerald-700 text-emerald-200 rounded-2xl text-xs font-semibold leading-relaxed flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 1118 0z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-950/60 border border-rose-700 text-rose-200 rounded-2xl text-xs font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').classList.add('opacity-50');">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email Terdaftar *</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           placeholder="nama@email.com" 
                           class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                    @error('email')
                        <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-rose-950/50 transition-all cursor-pointer flex items-center justify-center gap-2">
                    <span>Kirim Link Reset</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            <div class="pt-2 border-t border-slate-200 dark:border-slate-700/50 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Halaman Login</span>
                </a>
            </div>
        </div>

        <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center font-medium">
            &copy; {{ date('Y') }} {{ $branding['app_name'] ?? 'SELON BEAUTY' }}. All rights reserved.
        </p>

    </div>

</body>
</html>
