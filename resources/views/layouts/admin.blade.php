<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - {{ $branding['app_name'] }}</title>
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ collapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true', mobileOpen: false }" 
      x-init="$watch('collapsed', val => localStorage.setItem('admin_sidebar_collapsed', val))"
      class="bg-slate-100 text-slate-900 min-h-screen antialiased flex flex-col md:flex-row relative">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div x-show="mobileOpen" 
         @click="mobileOpen = false" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs md:hidden"
         style="display: none;"></div>

    <!-- Sidebar Desktop & Mobile Offcanvas Drawer -->
    <aside :class="{
                'translate-x-0': mobileOpen,
                '-translate-x-full md:translate-x-0': !mobileOpen,
                'md:w-64': !collapsed,
                'md:w-20': collapsed
           }"
           class="fixed md:static inset-y-0 left-0 z-50 bg-slate-900 text-slate-100 flex-shrink-0 flex flex-col justify-between border-r border-slate-800 transition-all duration-300 shadow-2xl md:shadow-none">
        
        <div class="min-h-0 flex-1 overflow-y-auto">
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center border-b border-slate-800 transition-all"
                 :class="collapsed ? 'justify-center px-2' : 'justify-between px-4'">
                <div class="flex items-center gap-3 overflow-hidden">
                    @if($branding['app_logo_url'])
                        <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}" class="w-9 h-9 object-contain rounded-xl shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-600 via-pink-600 to-rose-500 flex items-center justify-center font-black text-white text-sm shadow-md shadow-rose-900/40 shrink-0">
                            {{ strtoupper(substr($branding['app_name'], 0, 2)) }}
                        </div>
                    @endif
                    <div x-show="!collapsed" class="transition-opacity duration-200">
                        <h1 class="font-extrabold text-sm tracking-tight text-white leading-none whitespace-nowrap">{{ $branding['app_name'] }}</h1>
                        <span class="text-[10px] text-rose-400 font-bold uppercase tracking-wider block mt-0.5 whitespace-nowrap">Admin Portal</span>
                    </div>
                </div>

                <!-- Desktop Collapse Button (Only shown when expanded) -->
                <button type="button" @click="collapsed = !collapsed" x-show="!collapsed" class="hidden md:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer shrink-0" title="Ciutkan Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>

                <!-- Mobile Close Button -->
                <button type="button" @click="mobileOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 cursor-pointer shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1.5 text-xs font-semibold">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" 
                   :title="collapsed ? 'Dashboard Monitoring' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Dashboard</span>
                </a>

                <!-- Operational Exception Center -->
                <a href="{{ route('admin.operational-exceptions.index') }}"
                   :title="collapsed ? 'Pusat Perhatian' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   @if(request()->routeIs('admin.operational-exceptions.*')) aria-current="page" @endif
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.operational-exceptions.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Pusat Perhatian</span>
                </a>

                <!-- Karyawan -->
                <a href="{{ route('admin.employees.index') }}" 
                   :title="collapsed ? 'Kelola Karyawan' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.employees.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Karyawan</span>
                </a>

                <!-- Jabatan -->
                <a href="{{ route('admin.job-titles.index') }}" 
                   :title="collapsed ? 'Master Jabatan' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.job-titles.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Jabatan</span>
                </a>

                <!-- Shift Kerja -->
                <a href="{{ route('admin.shifts.index') }}" 
                   :title="collapsed ? 'Shift Kerja' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.shifts.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Shift Kerja</span>
                </a>

                <!-- Jadwal & Kalender -->
                <a href="{{ route('admin.schedules.index') }}" 
                   :title="collapsed ? 'Jadwal & Kalender' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.schedules.*', 'admin.work-calendar.*', 'admin.schedule-overrides.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Jadwal & Kalender</span>
                </a>

                <!-- Absensi -->
                <a href="{{ route('admin.attendance.index') }}" 
                   :title="collapsed ? 'Monitoring Absensi' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.attendance.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Absensi</span>
                </a>

                <!-- Izin & Cuti -->
                <a href="{{ route('admin.leave-requests.index') }}" 
                   :title="collapsed ? 'Persetujuan Izin & Cuti' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.leave-requests.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Izin & Cuti</span>
                </a>

                <!-- Lembur -->
                <a href="{{ route('admin.overtime-requests.index') }}" 
                   :title="collapsed ? 'Persetujuan Lembur' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.overtime-requests.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Lembur</span>
                </a>

                <!-- Tukar Jadwal -->
                <a href="{{ route('admin.shift-swaps.index') }}" 
                   :title="collapsed ? 'Permintaan Tukar Jadwal' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.shift-swaps.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span x-show="!collapsed" class="truncate">Permintaan Tukar Jadwal</span>
                </a>

                <!-- Laporan -->
                <a href="{{ route('admin.reports.attendance') }}" 
                   :title="collapsed ? 'Laporan & Export' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.reports.*', 'admin.monthly-recaps.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Laporan</span>
                </a>

                <!-- Pengaturan -->
                <a href="{{ route('admin.settings.attendance') }}" 
                   :title="collapsed ? 'Pengaturan Toko & Geofence' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Pengaturan</span>
                </a>

                @if(in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                    <a href="{{ route('admin.audit-logs.index') }}"
                       :title="collapsed ? 'Audit Trail' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3'"
                       class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.audit-logs.*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span x-show="!collapsed" class="truncate">Audit Trail</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer Profile Summary -->
        <div class="p-3 border-t border-slate-800 space-y-2">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-800/60 overflow-hidden"
                 :class="collapsed ? 'justify-center p-1.5' : ''">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 font-extrabold flex items-center justify-center text-xs border border-rose-500/30 shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                </div>
                <div x-show="!collapsed" class="overflow-hidden flex-1">
                    <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-pink-400 font-extrabold uppercase tracking-wider">{{ \App\Enums\UserRole::tryFrom(Auth::user()->role)?->label() ?? ucfirst(Auth::user()->role ?? 'Admin') }}</p>
                </div>
            </div>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" 
                        :title="collapsed ? 'Keluar (Logout)' : ''"
                        :class="collapsed ? 'justify-center px-0' : 'px-3'"
                        class="w-full flex items-center gap-2 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-rose-600 rounded-xl transition-all cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span x-show="!collapsed">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar Header -->
        <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            
            <!-- Left Side: Mobile Hamburger, Desktop Toggle & Page Title -->
            <div class="flex items-center gap-2">
                <!-- Mobile Hamburger Button -->
                <button type="button" @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer" title="Buka Menu Mobile">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <!-- Desktop Sidebar Toggle Button -->
                <button type="button" @click="collapsed = !collapsed" class="hidden md:flex p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer" title="Ciutkan / Perluas Sidebar">
                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </button>

                <h2 class="text-base md:text-lg font-black text-slate-900 tracking-tight ml-1">@yield('page-title', 'Dashboard')</h2>
            </div>

            <!-- Right Side: Action Shortcuts, Notification Bell, User Dropdown -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile Shell Link Shortcut -->
                <a href="{{ route('employee.dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 px-3 py-1.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>App Karyawan</span>
                </a>

                <!-- Notification Bell -->
                @auth
                    @php
                        $unreadCount = Auth::user()->unreadNotifications()->count();
                        $recentNotifications = Auth::user()->notifications()->take(5)->get();
                    @endphp
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button type="button" @click="notifOpen = !notifOpen" class="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifikasi System">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-rose-600 rounded-full border-2 border-white min-w-[18px] text-center">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Notification Dropdown Panel -->
                        <div x-show="notifOpen" 
                             @click.away="notifOpen = false" 
                             x-transition:enter="transition ease-out duration-100" 
                             x-transition:enter-start="transform opacity-0 scale-95" 
                             x-transition:enter-end="transform opacity-100 scale-100" 
                             x-transition:leave="transition ease-in duration-75" 
                             x-transition:leave-start="transform opacity-100 scale-100" 
                             x-transition:leave-end="transform opacity-0 scale-95" 
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100" 
                             style="display: none;">
                            <div class="px-4 py-3 flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900">Notifikasi ({{ $unreadCount }} Baru)</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-extrabold text-rose-600 hover:text-rose-700 cursor-pointer">Tandai Semua Dibaca</button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                                @forelse($recentNotifications as $n)
                                    @php $data = $n->data; $isUnread = is_null($n->read_at); @endphp
                                    <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 cursor-pointer {{ $isUnread ? 'bg-rose-50/40' : '' }}">
                                            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $isUnread ? 'bg-rose-600' : 'bg-transparent' }}"></div>
                                            <div class="flex-1 space-y-0.5">
                                                <p class="text-xs font-bold text-slate-800 leading-snug">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                                <p class="text-[11px] text-slate-600 line-clamp-2">{{ $data['message'] ?? '' }}</p>
                                                <span class="text-[9px] text-slate-400 font-semibold block pt-1">{{ $n->created_at->diffForHumans() }}</span>
                                            </div>
                                        </button>
                                    </form>
                                @empty
                                    <div class="p-6 text-center text-xs text-slate-500 font-medium">Belum ada notifikasi.</div>
                                @endforelse
                            </div>

                            <div class="px-4 py-2.5 bg-slate-50 text-center">
                                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700">Lihat Semua Notifikasi &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- User Dropdown Menu -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button type="button" @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 p-1.5 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer focus:outline-none">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white font-extrabold text-xs flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                        </div>
                        <span class="hidden md:inline text-xs font-extrabold text-slate-800">{{ Auth::user()->name ?? 'User' }}</span>
                        <svg class="w-4 h-4 text-slate-400 hidden md:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <!-- User Dropdown Panel -->
                    <div x-show="userMenuOpen" 
                         @click.away="userMenuOpen = false" 
                         x-transition:enter="transition ease-out duration-100" 
                         x-transition:enter-start="transform opacity-0 scale-95" 
                         x-transition:enter-end="transform opacity-100 scale-100" 
                         x-transition:leave="transition ease-in duration-75" 
                         x-transition:leave-start="transform opacity-100 scale-100" 
                         x-transition:leave-end="transform opacity-0 scale-95" 
                         class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100" 
                         style="display: none;">
                        
                        <div class="px-4 py-3">
                            <p class="text-xs font-black text-slate-900 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ Auth::user()->email ?? '' }}</p>
                            <span class="inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 rounded-md mt-1.5">
                                {{ Auth::user()->role ?? 'Admin' }}
                            </span>
                        </div>

                        <div class="py-1 text-xs">
                            <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-rose-600 font-bold transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Buka Portal Karyawan</span>
                            </a>
                            <a href="{{ route('admin.settings.attendance') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-rose-600 font-bold transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Pengaturan Toko</span>
                            </a>
                        </div>

                        <div class="py-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- Page Content Area -->
        <main class="flex-1 p-4 md:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            @yield('content')
        </main>
    </div>

</body>
</html>
