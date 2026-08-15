<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ $branding['app_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="manifest" href="{{ route('pwa.manifest', [], false) }}">
    <meta name="theme-color" content="{{ $branding['pwa_theme_color'] }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $branding['app_short_name'] }}">
    <link rel="apple-touch-icon" href="{{ $branding['app_icon_url'] }}">
    <link rel="icon" type="{{ $branding['favicon_mime_type'] }}" href="{{ $branding['favicon_url'] }}">
    <style>
        :root {
            --brand-primary: {{ $branding['brand_primary'] }};
            --brand-accent: {{ $branding['brand_accent'] }};
        }
    </style>
    @include('partials.theme_bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex items-center justify-center p-4 transition-colors">

    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden transition-colors">
        
        <!-- Header Branding Banner -->
        <div class="bg-gradient-to-r from-rose-700 via-rose-600 to-pink-600 p-8 text-white text-center relative">
            @if($branding['app_logo_url'])
                <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}" class="w-16 h-16 object-contain rounded-2xl mx-auto mb-3 shadow-lg shadow-rose-900/30">
            @else
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center mx-auto mb-3 text-2xl font-black shadow-lg shadow-rose-900/30 border border-white/30">
                    {{ strtoupper(substr($branding['app_name'], 0, 2)) }}
                </div>
            @endif
            <h1 class="text-2xl font-extrabold tracking-tight">{{ $branding['app_name'] }}</h1>
            <p class="text-xs text-rose-100 mt-1 font-medium">{{ $branding['app_tagline'] }}</p>
        </div>

        <!-- Login Form Container -->
        <div class="p-6 md:p-8 space-y-6">
            
            <!-- Flash Message Alerts -->
            @if (session('info'))
                <div class="p-3.5 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-blue-200 rounded-xl text-xs font-semibold">
                    {{ session('info') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-3.5 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Selamat Datang</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Silakan masuk menggunakan Email, Nomor HP, atau Kode Karyawan terdaftar.</p>
            </div>

            <form action="{{ url('/login') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Login Identifier Field -->
                <div>
                    <label for="login" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email / Nomor HP / Kode Karyawan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus placeholder="name@example.com atau 081234567890" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all bg-slate-50/50 dark:bg-slate-950 dark:text-slate-100 dark:placeholder-slate-500">
                    </div>
                    @error('login')
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="••••••••" class="w-full pl-11 pr-11 py-3 rounded-xl border border-slate-300 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all bg-slate-50/50 dark:bg-slate-950 dark:text-slate-100 dark:placeholder-slate-500">
                        <button type="button" id="toggle-password" aria-label="Tampilkan password" aria-controls="password" class="absolute inset-y-0 right-0 pr-3.5 pl-3 flex items-center min-w-[44px] min-h-[44px] justify-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none focus:text-slate-600 cursor-pointer">
                            <svg id="icon-eye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="icon-eye-off" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between py-1">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500">
                        <span>Ingat saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 transition-colors">
                        Lupa Password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold rounded-xl text-sm shadow-lg shadow-rose-600/30 transition-all transform active:scale-98 cursor-pointer flex items-center justify-center gap-2">
                    <span>Masuk ke Aplikasi</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>

            </form>

            @include('partials.auth_footer')

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
            const iconEye = document.getElementById('icon-eye');
            const iconEyeOff = document.getElementById('icon-eye-off');

            if (passwordInput && toggleButton) {
                toggleButton.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';

                    if (isHidden) {
                        toggleButton.setAttribute('aria-label', 'Sembunyikan password');
                        if (iconEye) iconEye.classList.add('hidden');
                        if (iconEyeOff) iconEyeOff.classList.remove('hidden');
                    } else {
                        toggleButton.setAttribute('aria-label', 'Tampilkan password');
                        if (iconEye) iconEye.classList.remove('hidden');
                        if (iconEyeOff) iconEyeOff.classList.add('hidden');
                    }
                });
            }
        });
    </script>

</body>
</html>
