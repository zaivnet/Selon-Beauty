@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $totalEmployees = $metrics['total_employees'] ?? 0;
    $presentToday = $metrics['present_today'] ?? 0;
    $lateToday = $metrics['late_today'] ?? 0;
    $pendingToday = $metrics['pending_check_in_today'] ?? 0;
    $leaveToday = $metrics['leave_today'] ?? 0;
    $presentRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;

    $previewCategoryKeys = collect($exceptions['items'])->pluck('category')->unique()->take(4);
    $previewItems = collect($exceptions['items'])->take(4);
@endphp

<div class="space-y-6 max-w-[1600px] mx-auto">
    <!-- Top Header Banner -->
    <header class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 md:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-600 dark:bg-rose-500"></span>
                <span class="text-[11px] font-black uppercase tracking-wider text-rose-700 dark:text-rose-400">Workforce Control</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight mt-1 ui-page-header">Status Operasional Hari Ini</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Ringkasan operasional hari ini &bull; {{ $todayFormatted }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-outlet-filter />

            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ $todayFormatted }}</span>
            </span>

            <a href="{{ route('admin.dashboard') }}" class="ui-btn ui-btn-secondary h-9 text-xs">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Refresh</span>
            </a>
        </div>
    </header>

    <!-- Top KPI Row (5 Cards) -->
    <section aria-label="Operational KPI Summary" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Karyawan -->
        <article class="ui-card flex flex-col justify-between hover:border-blue-300 dark:hover:border-blue-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Karyawan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-extrabold shrink-0 border border-blue-100 dark:border-blue-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $totalEmployees }}</p>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Terdaftar aktif</p>
            </div>
        </article>

        <!-- 2. Hadir -->
        <article class="ui-card flex flex-col justify-between hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hadir Hari Ini</span>
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

        <!-- 3. Terlambat -->
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

        <!-- 4. Belum Check-in -->
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

        <!-- 5. Izin / Cuti / Sakit -->
        <article class="ui-card flex flex-col justify-between hover:border-indigo-300 dark:hover:border-indigo-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Izin / Cuti / Sakit</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-extrabold shrink-0 border border-indigo-100 dark:border-indigo-900/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $leaveToday }}</p>
                <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">Pengajuan disetujui</p>
            </div>
        </article>
    </section>

    <!-- Operational Duty Roster Widget (Jadwal Piket) -->
    @if(isset($rosterData))
        <x-dashboard-duty-roster :roster-data="$rosterData" />
    @endif

    <!-- Main Content Grid (65% Left / 35% Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- MAIN LEFT (8 Cols = ~66% Width) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- A. Ringkasan Kehadiran Card -->
            <section class="ui-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Ringkasan Kehadiran Minggu Ini</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tren kehadiran karyawan 7 hari terakhir</p>
                    </div>
                    <span class="ui-badge ui-badge-indigo">
                        7 Hari Terakhir
                    </span>
                </div>

                @if(!empty($trendData['data']))
                    <div class="grid grid-cols-7 gap-2 pt-2">
                        @foreach($trendData['data'] as $day)
                            @php
                                $maxVal = max(1, $totalEmployees);
                                $heightPercent = min(100, max(15, round(($day['total'] / $maxVal) * 100)));
                                $isTodayDay = $day['date'] === Carbon\Carbon::now(config('app.timezone'))->toDateString();
                            @endphp
                            <div class="flex flex-col items-center gap-2 text-center group">
                                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300 font-mono">{{ $day['total'] }}</span>
                                <div class="w-full h-32 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden flex flex-col justify-end p-1 relative">
                                    <div class="w-full rounded-lg transition-all duration-300 {{ $isTodayDay ? 'bg-gradient-to-t from-rose-600 to-pink-500' : 'bg-slate-700 dark:bg-slate-600' }}" style="height: {{ $heightPercent }}%;"></div>
                                </div>
                                <span class="text-xs font-bold {{ $isTodayDay ? 'text-rose-600 dark:text-rose-400 font-black' : 'text-slate-500 dark:text-slate-400' }}">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                        Belum ada histori grafik kehadiran 7 hari terakhir.
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Hadir Tepat Waktu</span>
                        <p class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ max(0, $presentToday - $lateToday) }}</p>
                    </div>
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Terlambat</span>
                        <p class="text-base font-black text-amber-600 dark:text-amber-400 mt-0.5">{{ $lateToday }}</p>
                    </div>
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase">Izin / Cuti</span>
                        <p class="text-base font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $leaveToday }}</p>
                    </div>
                </div>
            </section>

            <!-- B. Status Karyawan Hari Ini (Data Table) -->
            <section class="ui-card space-y-4" x-data="{ search: '', statusFilter: 'all' }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Status Karyawan Hari Ini</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Monitoring absensi real-time karyawan aktif</p>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="text-xs font-extrabold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 flex items-center gap-1">
                        <span>Lihat Monitoring Lengkap &rarr;</span>
                    </a>
                </div>

                <!-- Live Client Search Toolbar & Status Dropdown Filter -->
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="flex-1 relative w-full sm:min-w-[240px]">
                        <input type="text"
                               x-model="search"
                               placeholder="Cari karyawan..."
                               aria-label="Cari karyawan"
                               class="ui-input pl-9 h-10 text-xs font-bold">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <select x-model="statusFilter"
                            aria-label="Filter status absensi"
                            class="ui-select w-full sm:w-auto h-10 text-xs font-bold">
                        <option value="all">Semua Status</option>
                        <option value="present">Hadir / Tepat Waktu</option>
                        <option value="late">Terlambat</option>
                        <option value="pending">Belum Check-in</option>
                        <option value="leave">Izin / Cuti</option>
                    </select>
                </div>

                <!-- Full Width Table Container -->
                <div class="ui-table-container">
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th class="py-2.5 px-3.5">Karyawan</th>
                                <th class="py-2.5 px-3.5">Shift</th>
                                <th class="py-2.5 px-3.5">Check-in</th>
                                <th class="py-2.5 px-3.5">Status</th>
                                <th class="py-2.5 px-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium">
                            @forelse(collect($attendanceItems)->take(7) as $item)
                                @php
                                    $emp = $item['employee'];
                                    $record = $item['record'];
                                    $statusKey = $item['status_key'];
                                    $badgeClass = match($statusKey) {
                                        'present' => 'ui-badge-emerald',
                                        'late' => 'ui-badge-amber',
                                        'pending' => 'ui-badge-rose',
                                        'leave', 'permission', 'sick' => 'ui-badge-indigo',
                                        default => 'ui-badge-slate',
                                    };
                                @endphp
                                <tr x-show="(search === '' || '{{ strtolower(addslashes($emp->full_name)) }}'.includes(search.toLowerCase()) || '{{ strtolower(addslashes($emp->employee_code ?? '')) }}'.includes(search.toLowerCase())) && (statusFilter === 'all' || '{{ $statusKey }}' === statusFilter || (statusFilter === 'leave' && ['permission', 'sick', 'leave'].includes('{{ $statusKey }}')))"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="py-2.5 px-3.5 font-extrabold text-slate-900 dark:text-slate-100">
                                        {{ $emp->full_name }}
                                        <span class="block text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ $emp->jobTitle?->name ?? 'Karyawan' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-slate-600 dark:text-slate-300 font-bold">
                                        {{ $item['effective_schedule']['shift']?->name ?? 'OFF' }}
                                    </td>
                                    <td class="py-2.5 px-3.5 font-mono text-slate-700 dark:text-slate-300 font-bold">
                                        {{ $record?->check_in_at ? $record->check_in_at->format('H:i') : '--:--' }}
                                    </td>
                                    <td class="py-2.5 px-3.5">
                                        <span class="ui-badge {{ $badgeClass }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-right">
                                        <a href="{{ route('admin.attendance.index', ['employee_id' => $emp->id]) }}" class="text-xs font-extrabold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">
                                            Detail &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs font-semibold">
                                        Belum ada data kehadiran untuk hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        <!-- MAIN RIGHT (4 Cols = ~34% Width) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- A. Perlu Perhatian (Operational Exceptions Preview Widget) -->
            <section class="ui-card space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Perlu Perhatian</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Pusat kendali pengecualian operasional</p>
                    </div>
                    <span class="ui-badge ui-badge-rose">
                        {{ $exceptions['summary']['total'] }} Isu
                    </span>
                </div>

                @if($exceptions['summary']['total'] === 0)
                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-300 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <div>
                            <p class="text-xs font-bold">Operasional hari ini aman</p>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">Tidak ada isu yang perlu perhatian saat ini.</p>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                        @foreach($previewItems as $item)
                            @include('admin.operational_exceptions._item', ['item' => $item, 'variant' => 'dashboard'])
                        @endforeach
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('admin.operational-exceptions.index') }}" class="ui-btn ui-btn-secondary w-full border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60">
                            <span>Lihat Semua Pusat Perhatian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                @endif
            </section>

            <!-- B. Quick Actions Widget -->
            <section class="ui-card space-y-3">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Aksi Cepat</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Navigasi cepat fitur manajemen</p>
                </div>

                <div class="space-y-1.5">
                    <a href="{{ route('admin.employees.create') }}" class="p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-700 dark:group-hover:text-rose-400">Tambah Karyawan</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.schedules.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-700 dark:group-hover:text-rose-400">Atur Jadwal & Kalender</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.leave-requests.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-700 dark:group-hover:text-rose-400">Persetujuan Izin & Cuti</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.shift-swaps.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-pink-50 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-700 dark:group-hover:text-rose-400">Permintaan Tukar Jadwal</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.reports.attendance') }}" class="p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-700 dark:group-hover:text-rose-400">Laporan & Recap Periode</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </section>

            <!-- C. Backup Operasional Widget (Owner/Superadmin) -->
            @if($exceptions['backup_health']['available'] && in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                @php
                    $backupCritical = $exceptions['backup_health']['severity'] === 'critical';
                @endphp
                <section class="ui-card space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Backup operasional</span>
                        <span class="ui-badge {{ $backupCritical ? 'ui-badge-rose' : 'ui-badge-emerald' }}">
                            {{ $backupCritical ? 'PERLU PERHATIAN' : 'SEHAT' }}
                        </span>
                    </div>

                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-snug">
                        {{ $exceptions['backup_health']['message'] }}
                    </p>

                    @if($exceptions['backup_health']['last_successful_at'])
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">
                            Berhasil terakhir: <span class="font-mono text-slate-700 dark:text-slate-300 font-bold">{{ $exceptions['backup_health']['last_successful_at']->format('d M Y H:i') }}</span>
                        </p>
                    @endif

                    @if(in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                        <a href="{{ route('admin.settings.backups.index') }}" class="ui-btn ui-btn-secondary w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border-transparent">
                            <span>Buka Backup</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </section>
            @endif

        </div>

    </div>
</div>
@endsection
