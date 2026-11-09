<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Portal Karyawan') · {{ $branding['company_name'] }} — {{ $branding['product_name'] }}</title>
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
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex flex-col justify-between max-w-md mx-auto shadow-2xl border-x border-slate-200/70 dark:border-slate-800/70 relative transition-colors overflow-x-hidden">

    <!-- Global Navigation Loader Overlay (Employee Skeleton) -->
    <div id="app-page-loader" class="flex flex-col h-full max-w-md mx-auto relative bg-slate-100 dark:bg-slate-950">
        <!-- Header Skeleton -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800/80 px-4 py-3 flex items-center justify-between shadow-xs" style="padding-top: max(0.75rem, env(safe-area-inset-top, 0px));">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl ui-skeleton ui-skeleton-card"></div>
                <div class="space-y-1">
                    <div class="h-4 w-32 ui-skeleton ui-skeleton-text rounded-md"></div>
                    <div class="h-2.5 w-24 ui-skeleton ui-skeleton-text rounded-md"></div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full ui-skeleton ui-skeleton-circle"></div>
                <div class="w-8 h-8 rounded-full ui-skeleton ui-skeleton-circle"></div>
            </div>
        </header>

        <!-- Body Skeleton -->
        <main class="flex-1 p-4 space-y-4">
            <!-- Greeting Card Skeleton -->
            <div class="h-24 ui-skeleton ui-skeleton-card rounded-2xl"></div>

            <!-- Quick Actions Skeleton -->
            <div class="grid grid-cols-3 gap-2.5">
                <div class="h-24 ui-skeleton ui-skeleton-card"></div>
                <div class="h-24 ui-skeleton ui-skeleton-card"></div>
                <div class="h-24 ui-skeleton ui-skeleton-card"></div>
            </div>

            <!-- Main Status Card Skeleton -->
            <div class="h-40 ui-skeleton ui-skeleton-card mt-2"></div>
            <div class="h-32 ui-skeleton ui-skeleton-card mt-2"></div>
        </main>

        <!-- Bottom Nav Skeleton -->
        <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white/95 dark:bg-slate-950/95 border-t border-slate-200/80 dark:border-slate-800/80 py-2 px-2 flex justify-around items-center" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0px));">
            <div class="w-10 h-10 ui-skeleton ui-skeleton-circle"></div>
            <div class="w-10 h-10 ui-skeleton ui-skeleton-circle"></div>
            <div class="w-12 h-12 ui-skeleton ui-skeleton-circle -mt-4 border-2 border-white dark:border-slate-900"></div>
            <div class="w-10 h-10 ui-skeleton ui-skeleton-circle"></div>
            <div class="w-10 h-10 ui-skeleton ui-skeleton-circle"></div>
        </nav>
    </div>

    <!-- Top Mobile Header -->
    <header class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md text-slate-900 dark:text-slate-100 border-b border-slate-200/80 dark:border-slate-800/80 px-4 py-2.5 flex items-center justify-between sticky top-0 z-20 shadow-xs" style="padding-top: max(0.75rem, env(safe-area-inset-top, 0px));">
        <div class="flex items-center gap-2.5 min-w-0">
            @if($branding['app_logo_url'])
                <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['company_name'] }}" class="w-9 h-9 object-contain rounded-xl shadow-xs shrink-0 bg-slate-50 dark:bg-slate-800 p-0.5 border border-slate-200/60 dark:border-slate-700/60">
            @else
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-600 to-sky-600 text-white flex items-center justify-center font-black text-xs tracking-wider shadow-sm shrink-0">
                    {{ strtoupper(substr($branding['company_name'], 0, 2)) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="font-black text-xs sm:text-sm tracking-tight leading-tight text-slate-900 dark:text-slate-100 truncate">{{ $branding['company_name'] }}</h1>
                <span class="text-[10px] text-teal-700 dark:text-teal-400 font-semibold block truncate">Portal Karyawan · {{ $branding['product_name'] }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @auth
                @php
                    $unreadCount = Auth::user()->unreadNotifications()->count();
                    $recentNotifications = Auth::user()->notifications()->take(5)->get();
                    $userInitials = strtoupper(substr(Auth::user()->name, 0, 2));
                @endphp

                <!-- Theme Switcher Dropdown (Alpine.js) -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" aria-label="Ganti Tema Tampilan" class="relative p-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full transition-all cursor-pointer focus:outline-none min-h-[44px] min-w-[44px] flex items-center justify-center" title="Pilih Tema">
                        <!-- Sun Icon for Light Mode -->
                        <svg class="w-4 h-4 dark:hidden text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <!-- Moon Icon for Dark Mode -->
                        <svg class="w-4 h-4 hidden dark:block text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-36 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-1.5 z-50 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                        <button type="button" @click="window.applyAttendanceTheme('light'); open = false" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Terang</span>
                        </button>
                        <button type="button" @click="window.applyAttendanceTheme('dark'); open = false" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span>Gelap</span>
                        </button>
                        <button type="button" @click="window.applyAttendanceTheme('system'); open = false" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Sistem</span>
                        </button>
                    </div>
                </div>

                <!-- Notification Bell -->
                <div id="employee-notification-menu" class="relative">
                    <button id="employee-notification-toggle" type="button" aria-expanded="false" aria-controls="employee-notification-dropdown" class="relative p-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full transition-all cursor-pointer focus:outline-none min-h-[44px] min-w-[44px] flex items-center justify-center" title="Notifikasi" aria-label="Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($unreadCount > 0)
                            <span class="absolute top-1 right-1 px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-rose-600 rounded-full border-2 border-white dark:border-slate-900 min-w-[16px] text-center">
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
                                    <button type="submit" class="text-[10px] font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">Tandai Dibaca</button>
                                </form>
                            @endif
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
                            @forelse($recentNotifications as $n)
                                @php $data = $n->data; $isUnread = is_null($n->read_at); @endphp
                                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left p-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors flex items-start gap-2.5 cursor-pointer {{ $isUnread ? 'bg-teal-50/50 dark:bg-teal-950/20' : '' }}">
                                        <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $isUnread ? 'bg-teal-600' : 'bg-transparent' }}"></div>
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
                            <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">Lihat Semua &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Admin Link (if Owner/Admin) -->
                @if(in_array(Auth::user()->role, ['owner', 'admin']))
                    <a href="{{ route('admin.dashboard') }}" class="text-[10px] font-extrabold bg-teal-50 hover:bg-teal-100 dark:bg-teal-950/60 dark:hover:bg-teal-900/60 text-teal-800 dark:text-teal-300 border border-teal-200 dark:border-teal-800/60 px-2.5 py-1.5 rounded-full transition-all min-h-[44px] flex items-center">
                        Admin &rarr;
                    </a>
                @endif

                <!-- Profile Avatar Shortcut -->
                <a href="{{ route('employee.profile.index') }}" class="w-9 h-9 rounded-full bg-slate-900 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-white font-extrabold text-xs flex items-center justify-center transition-all min-h-[44px] min-w-[44px] hover:ring-2 hover:ring-teal-500" title="Profil" aria-label="Profil Karyawan">
                    {{ $userInitials }}
                </a>
            @endauth
        </div>
    </header>

    <!-- Main Content Container with Safe Area Padding -->
    <main class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-100 dark:bg-slate-950" style="padding-bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px));">
        @yield('content')
    </main>

    <!-- Bottom Navigation Bar (5 Items Max, Safe-Area Compatible) -->
    @php($attendanceParticipationEnabled = Auth::user()?->role !== 'superadmin' && Auth::user()?->employee?->attendance_enabled !== false)
    <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/80 dark:border-slate-800/80 py-1.5 px-2 flex justify-around items-center z-30 shadow-lg" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0px));">
        <!-- 1. Home -->
        <a href="{{ route('employee.dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 px-3 py-1 rounded-xl transition-all min-h-[44px] min-w-[48px] {{ request()->routeIs('employee.dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-[10px]">Home</span>
        </a>

        <!-- 2. Jadwal -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.schedules.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-3 py-1 rounded-xl transition-all min-h-[44px] min-w-[48px] {{ request()->routeIs('employee.schedules.*', 'employee.monthly-recap.*') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-[10px]">Jadwal</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="flex min-h-[44px] min-w-[48px] flex-col items-center justify-center gap-0.5 px-3 py-1 text-slate-300 dark:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-[10px]">Jadwal</span>
        </span>
        @endif

        <!-- 3. Absen (Center CTA Button) -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.dashboard') }}#absen-card" class="flex flex-col items-center justify-center gap-0.5 group -mt-5 min-h-[52px] min-w-[52px]">
            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-teal-600 via-teal-500 to-sky-500 text-white flex items-center justify-center shadow-lg shadow-teal-600/30 group-hover:scale-105 active:scale-95 transition-transform border-2 border-white dark:border-slate-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <span class="text-[10px] font-extrabold text-rose-600 dark:text-rose-400">Absen</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="-mt-5 flex min-h-[52px] min-w-[52px] flex-col items-center justify-center gap-0.5">
            <span class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/></svg>
            </span>
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">Absen</span>
        </span>
        @endif

        <!-- 4. Pengajuan (Izin, Lembur & Tukar Jadwal) -->
        @if($attendanceParticipationEnabled)
        <a href="{{ route('employee.leave-requests.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-3 py-1 rounded-xl transition-all min-h-[44px] min-w-[48px] {{ (request()->routeIs('employee.leave-requests.*') || request()->routeIs('employee.overtime-requests.*') || request()->routeIs('employee.shift-swaps.*')) ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[10px]">Pengajuan</span>
        </a>
        @else
        <span aria-disabled="true" title="Sistem kehadiran dinonaktifkan" class="flex min-h-[44px] min-w-[48px] flex-col items-center justify-center gap-0.5 px-3 py-1 text-slate-300 dark:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[10px]">Pengajuan</span>
        </span>
        @endif

        <!-- 5. Profil -->
        <a href="{{ route('employee.profile.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-3 py-1 rounded-xl transition-all min-h-[44px] min-w-[48px] {{ request()->routeIs('employee.profile.*') ? 'text-rose-600 font-extrabold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
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
                    console.log('PWA SW Registered:', reg.scope);
                }).catch(function(err) {
                    console.warn('PWA SW Registration Failed:', err);
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
