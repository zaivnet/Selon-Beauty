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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen antialiased flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        
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
                <div class="p-3.5 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-semibold">
                    {{ session('info') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <div>
                <h2 class="text-lg font-bold text-slate-800">Selamat Datang</h2>
                <p class="text-xs text-slate-500 mt-0.5">Silakan masuk menggunakan Email, Nomor HP, atau Kode Karyawan terdaftar.</p>
            </div>

            <form action="{{ url('/login') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Login Identifier Field (Email, Phone, or Employee Code) -->
                <div>
                    <label for="login" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email / Nomor HP / Kode Karyawan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus placeholder="contoh@selonbeauty.com, 081234567890, atau SB-001" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all bg-slate-50/50">
                    </div>
                    @error('login')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="••••••••" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all bg-slate-50/50">
                    </div>
                    @error('password')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me Checkbox & Forgot Password Link -->
                <div class="flex items-center justify-between py-1">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>Ingat saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 transition-colors">
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

</body>
</html>
