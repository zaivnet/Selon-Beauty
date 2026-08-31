@extends('layouts.admin')

@section('title', 'Global Operational Dashboard')
@section('page-title', 'Global Dashboard')

@section('content')
@php
    $globalKpi = $globalData['global_kpi'] ?? [];
    $totalOutlets = $globalKpi['total_outlets'] ?? 0;
    $totalEmployees = $globalKpi['total_employees'] ?? 0;
    $presentToday = $globalKpi['present_today'] ?? 0;
    $lateToday = $globalKpi['late_today'] ?? 0;
    $pendingToday = $globalKpi['pending_today'] ?? 0;
    $leaveToday = $globalKpi['leave_today'] ?? 0;
    $presentRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
@endphp

<div class="space-y-6 max-w-[1600px] mx-auto">
    <!-- Top Header Banner -->
    <header class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 md:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-600 dark:bg-indigo-500 shadow-[0_0_8px_rgba(79,70,229,0.6)]"></span>
                <span class="text-[11px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Owner & Superadmin</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight mt-1 ui-page-header">Status Operasional Global</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Ringkasan operasional di seluruh outlet &bull; {{ $todayFormatted }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-outlet-filter />

            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ $todayFormatted }}</span>
            </span>

            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 px-3.5 py-2 rounded-xl transition-all shadow-xs cursor-pointer">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Refresh</span>
            </a>
        </div>
    </header>

    <!-- Global KPI Row (6 Cards) -->
    <section aria-label="Global KPI Summary" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <!-- 1. Total Outlet -->
        <article class="ui-card flex flex-col justify-between hover:border-indigo-300 dark:hover:border-indigo-700 transition-all border-l-4 border-l-indigo-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Outlet</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-extrabold shrink-0 border border-indigo-100 dark:border-indigo-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $totalOutlets }}</p>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Outlet beroperasi aktif</p>
            </div>
        </article>

        <!-- 2. Total Karyawan -->
        <article class="ui-card flex flex-col justify-between hover:border-blue-300 dark:hover:border-blue-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Karyawan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-extrabold shrink-0 border border-blue-100 dark:border-blue-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $totalEmployees }}</p>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Seluruh outlet</p>
            </div>
        </article>

        <!-- 3. Hadir -->
        <article class="ui-card flex flex-col justify-between hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hadir Global</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-extrabold shrink-0 border border-emerald-100 dark:border-emerald-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $presentToday }}</p>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">({{ $presentRate }}%)</span>
                </div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Tepat waktu & terlambat</p>
            </div>
        </article>

        <!-- 4. Terlambat -->
        <article class="ui-card flex flex-col justify-between hover:border-amber-300 dark:hover:border-amber-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terlambat</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-extrabold shrink-0 border border-amber-100 dark:border-amber-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $lateToday }}</p>
                <p class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 mt-1">Koreksi & evaluasi</p>
            </div>
        </article>

        <!-- 5. Belum Check-in -->
        <article class="ui-card flex flex-col justify-between hover:border-rose-300 dark:hover:border-rose-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Belum Check-in</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-extrabold shrink-0 border border-rose-100 dark:border-rose-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $pendingToday }}</p>
                <p class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 mt-1">Menunggu kehadiran</p>
            </div>
        </article>

        <!-- 6. Izin / Cuti / Sakit -->
        <article class="ui-card flex flex-col justify-between hover:border-purple-300 dark:hover:border-purple-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Izin / Cuti</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-extrabold shrink-0 border border-purple-100 dark:border-purple-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $leaveToday }}</p>
                <p class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 mt-1">Seluruh outlet</p>
            </div>
        </article>
    </section>

    <!-- Operational Duty Roster Widget (Jadwal Piket) -->
    @if(isset($rosterData))
        <x-dashboard-duty-roster :roster-data="$rosterData" />
    @endif

    <!-- Operational Alert Widget -->
    @if($globalKpi['total_exceptions'] > 0)
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-start sm:items-center gap-3">
            <div class="mt-0.5 sm:mt-0 p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-black text-amber-900 dark:text-amber-300">Ditemukan {{ $globalKpi['total_exceptions'] }} Isu Operasional</h3>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5 font-medium">Beberapa outlet memerlukan perhatian operasional. Klik "Lihat Outlet" untuk memeriksa lebih detail.</p>
            </div>
        </div>
    @endif

    <!-- Backup Operasional Widget (Owner/Superadmin) -->
    @if(isset($exceptions['backup_health']) && $exceptions['backup_health']['available'] && in_array(Auth::user()->role, ['owner', 'superadmin'], true))
        @php
            $backupCritical = $exceptions['backup_health']['severity'] === 'critical';
        @endphp
        <div class="p-4 rounded-xl {{ $backupCritical ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60' : 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60' }} border flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <div class="flex items-start sm:items-center gap-3">
                <div class="mt-0.5 sm:mt-0 p-1.5 rounded-lg {{ $backupCritical ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400' : 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400' }} shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black {{ $backupCritical ? 'text-rose-900 dark:text-rose-300' : 'text-emerald-900 dark:text-emerald-300' }}">Backup operasional</h3>
                        <span class="ui-badge {{ $backupCritical ? 'ui-badge-rose' : 'ui-badge-emerald' }} !py-0.5 !px-2 !text-[10px]">{{ $backupCritical ? 'PERLU PERHATIAN' : 'SEHAT' }}</span>
                    </div>
                    <p class="text-xs font-bold {{ $backupCritical ? 'text-rose-800 dark:text-rose-200' : 'text-emerald-800 dark:text-emerald-200' }} mt-1 leading-snug">
                        {{ $exceptions['backup_health']['message'] }}
                    </p>
                    @if($exceptions['backup_health']['last_successful_at'])
                        <p class="text-[11px] {{ $backupCritical ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }} mt-0.5 font-semibold">
                            Berhasil terakhir: <span class="font-mono font-bold">{{ $exceptions['backup_health']['last_successful_at']->format('d M Y H:i') }}</span>
                        </p>
                    @endif
                </div>
            </div>
            
            <a href="{{ route('admin.settings.backups.index') }}" class="shrink-0 w-full sm:w-auto py-2 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 font-extrabold rounded-xl text-xs flex items-center justify-center gap-1.5 transition-colors shadow-xs">
                <span>Buka Backup</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @endif

    <!-- Main Content: Outlets Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-slate-100">Status Per Outlet</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Monitoring performa operasional masing-masing cabang</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($globalData['outlets'] ?? [] as $outletData)
                @php
                    $outlet = $outletData['outlet'];
                    $m = $outletData['metrics'];
                    $needsAttention = $outletData['needs_attention'];
                    $criticalCount = $outletData['critical_count'];
                    
                    // Determine card border and badge colors based on severity
                    $cardBorder = $criticalCount > 0 ? 'border-rose-300 dark:border-rose-700 shadow-[0_0_15px_rgba(225,29,72,0.1)]' : 
                                 ($needsAttention ? 'border-amber-300 dark:border-amber-700' : 'border-slate-200/80 dark:border-slate-800');
                    $statusBadge = $criticalCount > 0 ? '<span class="ui-badge ui-badge-rose px-2 py-0.5 text-[10px]"><span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1 animate-pulse"></span>KRITIS ('.$criticalCount.')</span>' :
                                  ($needsAttention ? '<span class="ui-badge ui-badge-amber px-2 py-0.5 text-[10px]">PERLU PERHATIAN</span>' : '<span class="ui-badge ui-badge-emerald px-2 py-0.5 text-[10px]">AMAN</span>');
                @endphp
                <article class="ui-card flex flex-col justify-between hover:shadow-md transition-all {{ $cardBorder }}">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-start justify-between gap-2 mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 truncate" title="{{ $outlet->name }}">{{ $outlet->name }}</h3>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $outlet->address ?? 'Alamat belum diatur' }}</p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                {!! $statusBadge !!}
                            </div>
                        </div>

                        <!-- Mini Metrics Grid -->
                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                                <span class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Hadir / Total</span>
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 mt-0.5">
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $m['present'] }}</span> <span class="text-slate-400">/</span> {{ $m['total_employees'] }}
                                </p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                                <span class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Terlambat</span>
                                <p class="text-sm font-black {{ $m['late'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500' }} mt-0.5">{{ $m['late'] }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                                <span class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Izin/Cuti</span>
                                <p class="text-sm font-black text-slate-700 dark:text-slate-300 mt-0.5">{{ $m['leave'] }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                                <span class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Total Isu</span>
                                <p class="text-sm font-black {{ $outletData['exceptions_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500' }} mt-0.5">{{ $outletData['exceptions_count'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="w-full m-0">
                            <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">
                            <button type="submit" class="w-full py-2 px-3 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 font-extrabold rounded-xl text-xs flex items-center justify-center gap-1.5 transition-colors shadow-xs">
                                <span>Lihat Dashboard Outlet</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach

            @if(count($globalData['outlets'] ?? []) === 0)
                <div class="col-span-full py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Tidak ada outlet aktif yang ditemukan.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
