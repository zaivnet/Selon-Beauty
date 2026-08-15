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
    <script>
        (function() {
            try {
                const theme = localStorage.getItem('attendance-theme') || 'system';
                if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{
          collapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true',
          mobileOpen: false,
          theme: localStorage.getItem('attendance-theme') || 'system',
          themeMenuOpen: false,
          setTheme(val) {
              this.theme = val;
              try {
                  localStorage.setItem('attendance-theme', val);
              } catch(e) {}
              if (val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }"
      x-init="
          $watch('collapsed', val => { try { localStorage.setItem('admin_sidebar_collapsed', val) } catch(e){} });
          const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
          const handleSystemThemeChange = (e) => {
              if (theme === 'system') {
                  if (e.matches) {
                      document.documentElement.classList.add('dark');
                  } else {
                      document.documentElement.classList.remove('dark');
                  }
              }
          };
          if (mediaQuery.addEventListener) {
              mediaQuery.addEventListener('change', handleSystemThemeChange);
          }
      "
      class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen antialiased flex flex-col md:flex-row relative transition-colors duration-200">

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
           class="fixed md:static inset-y-0 left-0 z-50 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 flex-shrink-0 flex flex-col justify-between border-r border-slate-200 dark:border-slate-800/80 transition-all duration-300 shadow-xl md:shadow-none">

        <div class="min-h-0 flex-1 overflow-y-auto">
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center border-b border-slate-200 dark:border-slate-800 transition-all"
                 :class="collapsed ? 'justify-center px-2' : 'justify-between px-4'">
                <div class="flex items-center gap-3 overflow-hidden">
                    @if($branding['app_logo_url'])
                        <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}" class="w-9 h-9 object-contain rounded-xl shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 flex items-center justify-center font-black text-white text-sm shadow-md shadow-rose-500/20 shrink-0">
                            {{ strtoupper(substr($branding['app_name'], 0, 2)) }}
                        </div>
                    @endif
                    <div x-show="!collapsed" class="transition-opacity duration-200">
                        <h1 class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white leading-none whitespace-nowrap">{{ $branding['app_name'] }}</h1>
                        <span class="text-[10px] text-rose-600 dark:text-rose-400 font-extrabold uppercase tracking-wider block mt-0.5 whitespace-nowrap">Admin Portal</span>
                    </div>
                </div>

                <!-- Desktop Collapse Button (Only shown when expanded) -->
                <button type="button" @click="collapsed = !collapsed" x-show="!collapsed" class="hidden md:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0" title="Ciutkan Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>

                <!-- Mobile Close Button -->
                <button type="button" @click="mobileOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1.5 text-xs font-semibold">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                   :title="collapsed ? 'Dashboard Monitoring' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.dashboard') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Dashboard</span>
                </a>

                <!-- Operational Exception Center -->
                <a href="{{ route('admin.operational-exceptions.index') }}"
                   :title="collapsed ? 'Pusat Perhatian' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   @if(request()->routeIs('admin.operational-exceptions.*')) aria-current="page" @endif
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.operational-exceptions.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Pusat Perhatian</span>
                </a>

                <!-- Karyawan -->
                <a href="{{ route('admin.employees.index') }}"
                   :title="collapsed ? 'Kelola Karyawan' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.employees.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Karyawan</span>
                </a>

                <!-- Jabatan -->
                <a href="{{ route('admin.job-titles.index') }}"
                   :title="collapsed ? 'Master Jabatan' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.job-titles.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Jabatan</span>
                </a>

                <!-- Shift Kerja -->
                <a href="{{ route('admin.shifts.index') }}"
                   :title="collapsed ? 'Shift Kerja' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.shifts.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Shift Kerja</span>
                </a>

                <!-- Jadwal & Kalender -->
                <a href="{{ route('admin.schedules.index') }}"
                   :title="collapsed ? 'Jadwal & Kalender' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.schedules.*', 'admin.work-calendar.*', 'admin.schedule-overrides.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Jadwal & Kalender</span>
                </a>

                <!-- Absensi -->
                <a href="{{ route('admin.attendance.index') }}"
                   :title="collapsed ? 'Monitoring Absensi' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.attendance.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Absensi</span>
                </a>

                <!-- Izin & Cuti -->
                <a href="{{ route('admin.leave-requests.index') }}"
                   :title="collapsed ? 'Persetujuan Izin & Cuti' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.leave-requests.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Izin & Cuti</span>
                </a>

                <!-- Lembur -->
                <a href="{{ route('admin.overtime-requests.index') }}"
                   :title="collapsed ? 'Persetujuan Lembur' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.overtime-requests.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Lembur</span>
                </a>

                <!-- Tukar Jadwal -->
                <a href="{{ route('admin.shift-swaps.index') }}"
                   :title="collapsed ? 'Permintaan Tukar Jadwal' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.shift-swaps.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span x-show="!collapsed" class="truncate">Permintaan Tukar Jadwal</span>
                </a>

                <!-- Laporan -->
                <a href="{{ route('admin.reports.attendance') }}"
                   :title="collapsed ? 'Laporan & Export' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.reports.*', 'admin.monthly-recaps.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                    <span x-show="!collapsed" class="truncate">Laporan</span>
                </a>

                <!-- Pengaturan -->
                <a href="{{ route('admin.settings.attendance') }}"
                   :title="collapsed ? 'Pengaturan Toko & Geofence' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3'"
                   class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.settings.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span x-show="!collapsed" class="truncate">Pengaturan</span>
                </a>

                @if(in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                    <a href="{{ route('admin.audit-logs.index') }}"
                       :title="collapsed ? 'Audit Trail' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3'"
                       class="flex items-center gap-3 py-2.5 rounded-xl transition-all relative group {{ request()->routeIs('admin.audit-logs.*') ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 font-extrabold border-l-4 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-900' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span x-show="!collapsed" class="truncate">Audit Trail</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer Profile Summary -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 space-y-2 bg-slate-50/50 dark:bg-slate-950">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 overflow-hidden"
                 :class="collapsed ? 'justify-center p-1.5' : ''">
                <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 font-extrabold flex items-center justify-center text-xs border border-rose-200 dark:border-rose-800 shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                </div>
                <div x-show="!collapsed" class="overflow-hidden flex-1">
                    <p class="text-xs font-extrabold text-slate-900 dark:text-slate-100 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-rose-600 dark:text-rose-400 font-extrabold uppercase tracking-wider">{{ \App\Enums\UserRole::tryFrom(Auth::user()->role)?->label() ?? ucfirst(Auth::user()->role ?? 'Admin') }}</p>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        :title="collapsed ? 'Keluar (Logout)' : ''"
                        :class="collapsed ? 'justify-center px-0' : 'px-3'"
                        class="w-full flex items-center gap-2 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 bg-white dark:bg-slate-900 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-slate-200/80 dark:border-slate-800 rounded-xl transition-all cursor-pointer">
                    <svg class="w-4 h-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span x-show="!collapsed">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-100 dark:bg-slate-950 transition-colors">
        <!-- Topbar Header -->
        <header class="h-16 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs transition-colors">

            <!-- Left Side: Mobile Hamburger, Desktop Toggle & Page Title -->
            <div class="flex items-center gap-2">
                <!-- Mobile Hamburger Button -->
                <button type="button" @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer" title="Buka Menu Mobile">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <!-- Desktop Sidebar Toggle Button -->
                <button type="button" @click="collapsed = !collapsed" class="hidden md:flex p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer" title="Ciutkan / Perluas Sidebar">
                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </button>

                <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-slate-100 tracking-tight ml-1">@yield('page-title', 'Dashboard')</h2>
            </div>

            <!-- Right Side: Action Shortcuts, Theme Toggle, Notification Bell, User Dropdown -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile Shell Link Shortcut -->
                <a href="{{ route('employee.dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 px-3 py-1.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>App Karyawan</span>
                </a>

                <!-- Adaptive Theme Selector Toggle -->
                <div class="relative" x-data="{ themeOpen: false }">
                    <button type="button"
                            @click="themeOpen = !themeOpen"
                            class="p-2 min-w-[40px] min-h-[40px] flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500"
                            aria-label="Pilih Tema (Terang / Gelap / Sistem)"
                            :title="theme === 'light' ? 'Mode Terang' : (theme === 'dark' ? 'Mode Gelap' : 'Ikuti Sistem')">

                        <!-- Sun Icon (Light Mode Active) -->
                        <svg x-show="theme === 'light'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>

                        <!-- Moon Icon (Dark Mode Active) -->
                        <svg x-show="theme === 'dark'" class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>

                        <!-- Monitor Icon (System Active) -->
                        <svg x-show="theme === 'system'" class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </button>

                    <!-- Theme Dropdown Panel -->
                    <div x-show="themeOpen"
                         @click.away="themeOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-44 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-1.5 z-50 space-y-0.5"
                         style="display: none;">

                        <button type="button"
                                @click="setTheme('light'); themeOpen = false"
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                                :class="{ 'text-rose-600 dark:text-rose-400 font-extrabold bg-rose-50 dark:bg-rose-950/40': theme === 'light' }">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Mode Terang</span>
                        </button>

                        <button type="button"
                                @click="setTheme('dark'); themeOpen = false"
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                                :class="{ 'text-rose-600 dark:text-rose-400 font-extrabold bg-rose-50 dark:bg-rose-950/40': theme === 'dark' }">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span>Mode Gelap</span>
                        </button>

                        <button type="button"
                                @click="setTheme('system'); themeOpen = false"
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                                :class="{ 'text-rose-600 dark:text-rose-400 font-extrabold bg-rose-50 dark:bg-rose-950/40': theme === 'system' }">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Ikuti Sistem</span>
                        </button>
                    </div>
                </div>

                <!-- Notification Bell -->
                @auth
                    @php
                        $unreadCount = Auth::user()->unreadNotifications()->count();
                        $recentNotifications = Auth::user()->notifications()->take(5)->get();
                    @endphp
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button type="button" @click="notifOpen = !notifOpen" class="relative p-2 min-w-[40px] min-h-[40px] flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifikasi System">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-rose-600 rounded-full border-2 border-white dark:border-slate-900 min-w-[18px] text-center">
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
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-2 z-50 divide-y divide-slate-100 dark:divide-slate-800"
                             style="display: none;">
                            <div class="px-4 py-3 flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900 dark:text-slate-100">Notifikasi ({{ $unreadCount }} Baru)</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-extrabold text-rose-600 dark:text-rose-400 hover:text-rose-700 cursor-pointer">Tandai Semua Dibaca</button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800/60">
                                @forelse($recentNotifications as $n)
                                    @php $data = $n->data; $isUnread = is_null($n->read_at); @endphp
                                    <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors flex items-start gap-3 cursor-pointer {{ $isUnread ? 'bg-rose-50/40 dark:bg-rose-950/20' : '' }}">
                                            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $isUnread ? 'bg-rose-600 dark:bg-rose-400' : 'bg-transparent' }}"></div>
                                            <div class="flex-1 space-y-0.5">
                                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-snug">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                                <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2">{{ $data['message'] ?? '' }}</p>
                                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold block pt-1">{{ $n->created_at->diffForHumans() }}</span>
                                            </div>
                                        </button>
                                    </form>
                                @empty
                                    <div class="p-6 text-center text-xs text-slate-500 dark:text-slate-400 font-medium">Belum ada notifikasi.</div>
                                @endforelse
                            </div>

                            <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-900 text-center">
                                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700">Lihat Semua Notifikasi &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- User Dropdown Menu -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button type="button" @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer focus:outline-none">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white font-extrabold text-xs flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                        </div>
                        <span class="hidden md:inline text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ Auth::user()->name ?? 'User' }}</span>
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
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-2 z-50 divide-y divide-slate-100 dark:divide-slate-800"
                         style="display: none;">

                        <div class="px-4 py-3">
                            <p class="text-xs font-black text-slate-900 dark:text-slate-100 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ Auth::user()->email ?? '' }}</p>
                            <span class="inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 rounded-md mt-1.5">
                                {{ Auth::user()->role ?? 'Admin' }}
                            </span>
                        </div>

                        <div class="py-1 text-xs">
                            <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-rose-600 dark:hover:text-rose-400 font-bold transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Buka Portal Karyawan</span>
                            </a>
                            <a href="{{ route('admin.settings.attendance') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-rose-600 dark:hover:text-rose-400 font-bold transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Pengaturan Toko</span>
                            </a>
                        </div>

                        <div class="py-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer">
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
        <main class="flex-1 p-4 md:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6 bg-slate-100 dark:bg-slate-950 transition-colors">
            @yield('content')
        </main>
    </div>

</body>
</html>
