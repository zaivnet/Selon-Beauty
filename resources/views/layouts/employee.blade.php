<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Portal Karyawan') - {{ $branding['app_name'] }}</title>
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
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex flex-col justify-between max-w-md mx-auto shadow-2xl border-x border-slate-200/70 dark:border-slate-800/70 relative transition-colors">

    <!-- Top Header -->
    <header class="bg-gradient-to-r from-rose-700 via-rose-600 to-pink-600 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-20 shadow-md" style="padding-top: max(0.75rem, env(safe-area-inset-top, 0px));">
        <div class="flex items-center gap-2.5">
            @if($branding['app_logo_url'])
                <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}" class="w-8 h-8 object-contain rounded-xl shadow-xs">
            @else
                <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center font-black text-xs tracking-wider shadow-inner">
                    {{ strtoupper(substr($branding['app_name'], 0, 2)) }}
                </div>
            @endif
            <div>
                <h1 class="font-black text-sm tracking-tight leading-tight">{{ $branding['app_name'] }}</h1>
                <span class="text-[10px] text-rose-200 font-medium block">Employee Portal</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @auth
                @php
                    $unreadCount = Auth::user()->unreadNotifications()->count();
                    $recentNotifications = Auth::user()->notifications()->take(5)->get();
                    $userInitials = strtoupper(substr(Auth::user()->name, 0, 2));
                @endphp
                
                <!-- Notification Bell -->
                <div id="employee-notification-menu" class="relative">
                    <button id="employee-notification-toggle" type="button" aria-expanded="false" aria-controls="employee-notification-dropdown" class="relative p-2 text-rose-100 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all cursor-pointer focus:outline-none min-h-[44px] min-w-[44px] flex items-center justify-center" title="Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($unreadCount > 0)
                            <span class="absolute top-0.5 right-0.5 px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-pink-500 rounded-full border-2 border-rose-700 min-w-[16px] text-center">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown Panel (Mobile Optimized) -->
                    <div id="employee-notification-dropdown" class="hidden absolute right-0 mt-2 w-72 sm:w-80 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-2 z-50 divide-y divide-slate-100 dark:divide-slate-800">
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-black text-slate-900 dark:text-slate-100">Notifikasi ({{ $unreadCount }} Baru)</span>
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">Tandai Dibaca</button>
                                </form>
                            @endif
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
                            @forelse($recentNotifications as $n)
                                @php $data = $n->data; $isUnread = is_null($n->read_at); @endphp
                                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left p-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors flex items-start gap-2.5 cursor-pointer {{ $isUnread ? 'bg-rose-50/50 dark:bg-rose-950/20' : '' }}">
                                        <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $isUnread ? 'bg-rose-600' : 'bg-transparent' }}"></div>
                                        <div class="flex-1 space-y-0.5">
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-snug">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                            <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2">{{ $data['message'] ?? '' }}</p>
                                            <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold block pt-0.5">{{ $n->created_at->diffForHumans() }}</span>
                                        </div>
                                    </button>
                                </form>
                            @empty
                                <div class="p-4 text-center text-xs text-slate-500 dark:text-slate-400 font-medium">Belum ada notifikasi.</div>
                            @endforelse
                        </div>

                        <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/60 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">Lihat Semua &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Admin Link (if Owner/Admin) -->
                @if(in_array(Auth::user()->role, ['owner', 'admin']))
                    <a href="{{ route('admin.dashboard') }}" class="text-[10px] font-extrabold bg-white/15 hover:bg-white/25 px-2.5 py-1.5 rounded-full text-rose-100 border border-white/20 transition-all min-h-[36px] flex items-center">
                        Admin &rarr;
                    </a>
                @endif

                <!-- Profile Avatar Shortcut -->
                <a href="{{ route('employee.profile.index') }}" class="w-8 h-8 rounded-full bg-rose-900/40 hover:bg-rose-900/60 border border-white/30 text-white font-extrabold text-xs flex items-center justify-center transition-all min-h-[36px] min-w-[36px]" title="Profil">
                    {{ $userInitials }}
                </a>
            @endauth
        </div>
    </header>

    <!-- Main Content Container with Safe Area Padding -->
    <main class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-100 dark:bg-slate-950" style="padding-bottom: calc(6rem + env(safe-area-inset-bottom, 0px));">
        @yield('content')
    </main>

    <!-- Bottom Navigation Bar (5 Items Max, Safe-Area Compatible) -->
    @php($attendanceParticipationEnabled = Auth::user()?->role !== 'superadmin' && Auth::user()?->employee?->attendance_enabled !== false)
    <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white/95 dark:bg-slate-950/95 backdrop-blur-md border-t border-slate-200/80 dark:border-slate-800/80 py-2 px-2 flex justify-around items-center z-30 shadow-lg" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0px));">
        <!-- 1. Home -->
        <a href="{{ route('employee.dashboard') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl transition-all {{ request()->routeIs('employee.dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-[10px]">Home</span>
        </a>

        <!-- 2. Jadwal -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.schedules.index') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl transition-all {{ request()->routeIs('employee.schedules.*', 'employee.monthly-recap.*') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-[10px]">Jadwal</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="flex min-h-[44px] min-w-[44px] flex-col items-center justify-center gap-0.5 px-3 py-1 text-slate-300 dark:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-[10px]">Jadwal</span>
        </span>
        @endif

        <!-- 3. Absen (Center CTA Button) -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.dashboard') }}#absen-card" class="flex flex-col items-center gap-0.5 group -mt-4">
            <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/40 group-hover:scale-105 transition-transform border-2 border-white dark:border-slate-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <span class="text-[10px] font-extrabold text-rose-600 dark:text-rose-400">Absen</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="-mt-4 flex min-h-[52px] min-w-[52px] flex-col items-center justify-center gap-0.5">
            <span class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/></svg>
            </span>
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">Absen</span>
        </span>
        @endif

        <!-- 4. Pengajuan (Izin, Lembur & Tukar Jadwal) -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.leave-requests.index') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl transition-all {{ (request()->routeIs('employee.leave-requests.*') || request()->routeIs('employee.overtime-requests.*') || request()->routeIs('employee.shift-swaps.*')) ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[10px]">Pengajuan</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="flex min-h-[44px] min-w-[44px] flex-col items-center justify-center gap-0.5 px-3 py-1 text-slate-300 dark:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[10px]">Pengajuan</span>
        </span>
        @endif

        <!-- 5. Profil -->
        <a href="{{ route('employee.profile.index') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl transition-all {{ request()->routeIs('employee.profile.*') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-[10px]">Profil</span>
        </a>
    </nav>

    <!-- PWA Install Banner & Service Worker Script -->
    <script>
        let deferredPwaPrompt = null;

        const notificationMenu = document.getElementById('employee-notification-menu');
        const notificationToggle = document.getElementById('employee-notification-toggle');
        const notificationDropdown = document.getElementById('employee-notification-dropdown');

        function closeNotificationMenu() {
            notificationDropdown?.classList.add('hidden');
            notificationToggle?.setAttribute('aria-expanded', 'false');
        }

        notificationToggle?.addEventListener('click', function () {
            const willOpen = notificationDropdown.classList.contains('hidden');
            notificationDropdown.classList.toggle('hidden', !willOpen);
            notificationToggle.setAttribute('aria-expanded', String(willOpen));
        });

        document.addEventListener('click', function (event) {
            if (notificationMenu && !notificationMenu.contains(event.target)) {
                closeNotificationMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeNotificationMenu();
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('SELON BEAUTY PWA SW Registered:', reg.scope);
                }).catch(function(err) {
                    console.warn('SELON BEAUTY PWA SW Registration Failed:', err);
                });
            });
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPwaPrompt = e;
            const pwaBanner = document.getElementById('pwa-install-banner');
            if (pwaBanner && !window.matchMedia('(display-mode: standalone)').matches) {
                pwaBanner.classList.remove('hidden');
            }
        });

        function triggerPwaInstall() {
            if (deferredPwaPrompt) {
                deferredPwaPrompt.prompt();
                deferredPwaPrompt.userChoice.then((choiceResult) => {
                    deferredPwaPrompt = null;
                    const pwaBanner = document.getElementById('pwa-install-banner');
                    if (pwaBanner) pwaBanner.classList.add('hidden');
                });
            }
        }
    </script>
</body>
</html>
