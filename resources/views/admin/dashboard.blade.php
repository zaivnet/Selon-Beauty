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
    <header class="bg-white rounded-2xl border border-slate-200/80 p-5 md:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                <span class="text-[11px] font-black uppercase tracking-wider text-rose-700">Workforce Control</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight mt-1">Status Operasional Hari Ini</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Ringkasan operasional hari ini &bull; {{ $todayFormatted }}</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 bg-slate-100 px-3 py-2 rounded-xl border border-slate-200">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ $todayFormatted }}</span>
            </span>

            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 px-3.5 py-2 rounded-xl transition-all shadow-xs cursor-pointer">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Refresh</span>
            </a>
        </div>
    </header>

    <!-- Top KPI Row (5 Cards) -->
    <section aria-label="Operational KPI Summary" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Karyawan -->
        <article class="ui-card flex flex-col justify-between hover:border-blue-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Karyawan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-extrabold shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 leading-none">{{ $totalEmployees }}</p>
                <p class="text-[11px] font-semibold text-slate-500 mt-1">Terdaftar aktif</p>
            </div>
        </article>

        <!-- 2. Hadir -->
        <article class="ui-card flex flex-col justify-between hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hadir Hari Ini</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-extrabold shrink-0 border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl lg:text-3xl font-black text-slate-900 leading-none">{{ $presentToday }}</p>
                    <span class="text-xs font-bold text-emerald-600">({{ $presentRate }}%)</span>
                </div>
                <p class="text-[11px] font-semibold text-slate-500 mt-1">Tepat waktu & terlambat</p>
            </div>
        </article>

        <!-- 3. Terlambat -->
        <article class="ui-card flex flex-col justify-between hover:border-amber-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Terlambat</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-extrabold shrink-0 border border-amber-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 leading-none">{{ $lateToday }}</p>
                <p class="text-[11px] font-semibold text-amber-600 mt-1">Koreksi & evaluasi</p>
            </div>
        </article>

        <!-- 4. Belum Check-in -->
        <article class="ui-card flex flex-col justify-between hover:border-rose-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Check-in</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-extrabold shrink-0 border border-rose-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 leading-none">{{ $pendingToday }}</p>
                <p class="text-[11px] font-semibold text-rose-600 mt-1">Menunggu kehadiran</p>
            </div>
        </article>

        <!-- 5. Izin / Cuti / Sakit -->
        <article class="ui-card flex flex-col justify-between hover:border-indigo-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Izin / Cuti / Sakit</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-extrabold shrink-0 border border-indigo-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl lg:text-3xl font-black text-slate-900 leading-none">{{ $leaveToday }}</p>
                <p class="text-[11px] font-semibold text-indigo-600 mt-1">Pengajuan disetujui</p>
            </div>
        </article>
    </section>

    <!-- Main Content Grid (65% Left / 35% Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- MAIN LEFT (8 Cols = ~66% Width) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- A. Ringkasan Kehadiran Card -->
            <section class="ui-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Ringkasan Kehadiran Minggu Ini</h2>
                        <p class="text-xs text-slate-500 font-medium">Tren kehadiran karyawan 7 hari terakhir</p>
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
                                <span class="text-[11px] font-bold text-slate-600 font-mono">{{ $day['total'] }}</span>
                                <div class="w-full h-32 bg-slate-100 rounded-xl overflow-hidden flex flex-col justify-end p-1 relative">
                                    <div class="w-full rounded-lg transition-all duration-300 {{ $isTodayDay ? 'bg-gradient-to-t from-rose-600 to-pink-500' : 'bg-slate-700' }}" style="height: {{ $heightPercent }}%;"></div>
                                </div>
                                <span class="text-xs font-bold {{ $isTodayDay ? 'text-rose-600 font-black' : 'text-slate-500' }}">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-xs font-semibold text-slate-400">
                        Belum ada histori grafik kehadiran 7 hari terakhir.
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-3 pt-3 border-t border-slate-100 text-center">
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] font-extrabold text-slate-500 uppercase">Hadir Tepat Waktu</span>
                        <p class="text-base font-black text-emerald-600 mt-0.5">{{ max(0, $presentToday - $lateToday) }}</p>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] font-extrabold text-slate-500 uppercase">Terlambat</span>
                        <p class="text-base font-black text-amber-600 mt-0.5">{{ $lateToday }}</p>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] font-extrabold text-slate-500 uppercase">Izin / Cuti</span>
                        <p class="text-base font-black text-indigo-600 mt-0.5">{{ $leaveToday }}</p>
                    </div>
                </div>
            </section>

            <!-- B. Status Karyawan Hari Ini (Data Table) -->
            <section class="ui-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Status Karyawan Hari Ini</h2>
                        <p class="text-xs text-slate-500 font-medium">Monitoring absensi real-time karyawan aktif</p>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="text-xs font-extrabold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                        <span>Lihat Monitoring Lengkap</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <!-- Live Client Search & Status Filter -->
                <div class="flex flex-col sm:flex-row gap-2.5" x-data="{ search: '', statusFilter: 'all' }">
                    <div class="flex-1 relative">
                        <input type="text" x-model="search" placeholder="Cari nama karyawan..." class="w-full pl-9 pr-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <select x-model="statusFilter" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="all">Semua Status</option>
                        <option value="present">Hadir / Tepat Waktu</option>
                        <option value="late">Terlambat</option>
                        <option value="pending">Belum Check-in</option>
                        <option value="leave">Izin / Cuti</option>
                    </select>

                    <div class="overflow-x-auto w-full border border-slate-200/80 rounded-xl mt-2">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    <th class="py-2.5 px-3.5">Karyawan</th>
                                    <th class="py-2.5 px-3.5">Shift</th>
                                    <th class="py-2.5 px-3.5">Check-in</th>
                                    <th class="py-2.5 px-3.5">Status</th>
                                    <th class="py-2.5 px-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium">
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
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-2.5 px-3.5 font-extrabold text-slate-900">
                                            {{ $emp->full_name }}
                                            <span class="block text-[10px] font-semibold text-slate-400">{{ $emp->jobTitle?->name ?? 'Karyawan' }}</span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-slate-600 font-bold">
                                            {{ $item['effective_schedule']['shift']?->name ?? 'OFF' }}
                                        </td>
                                        <td class="py-2.5 px-3.5 font-mono text-slate-700 font-bold">
                                            {{ $record?->check_in_at ? $record->check_in_at->format('H:i') : '--:--' }}
                                        </td>
                                        <td class="py-2.5 px-3.5">
                                            <span class="ui-badge {{ $badgeClass }}">
                                                {{ $item['status_label'] }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-right">
                                            <a href="{{ route('admin.attendance.index', ['employee_id' => $emp->id]) }}" class="text-xs font-extrabold text-rose-600 hover:text-rose-700">
                                                Detail &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-slate-400 text-xs font-semibold">
                                            Belum ada data kehadiran untuk hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>

        <!-- MAIN RIGHT (4 Cols = ~34% Width) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- A. Perlu Perhatian (Operational Exceptions Preview Widget) -->
            <section class="ui-card space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Perlu Perhatian</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Pusat kendali pengecualian operasional</p>
                    </div>
                    <span class="ui-badge ui-badge-rose">
                        {{ $exceptions['summary']['total'] }} Isu
                    </span>
                </div>

                @if($exceptions['summary']['total'] === 0)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <div>
                            <p class="text-xs font-bold">Operasional hari ini aman</p>
                            <p class="text-[11px] text-emerald-700">Tidak ada isu yang perlu perhatian saat ini.</p>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 overflow-hidden">
                        @foreach($previewItems as $item)
                            @include('admin.operational_exceptions._item', ['item' => $item, 'variant' => 'dashboard'])
                        @endforeach
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('admin.operational-exceptions.index') }}" class="w-full py-2.5 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold rounded-xl text-xs flex items-center justify-center gap-1 transition-colors border border-rose-200">
                            <span>Lihat Semua Pusat Perhatian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                @endif
            </section>

            <!-- B. Quick Actions Widget -->
            <section class="ui-card space-y-3">
                <div class="border-b border-slate-100 pb-2.5">
                    <h2 class="text-base font-extrabold text-slate-900">Aksi Cepat</h2>
                    <p class="text-[11px] text-slate-500 font-medium">Navigasi cepat fitur manajemen</p>
                </div>

                <div class="space-y-1.5">
                    <a href="{{ route('admin.employees.create') }}" class="p-2.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Tambah Karyawan</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.schedules.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Atur Jadwal & Kalender</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.leave-requests.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Persetujuan Izin & Cuti</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.shift-swaps.index') }}" class="p-2.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Permintaan Tukar Jadwal</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('admin.reports.attendance') }}" class="p-2.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Laporan & Recap Periode</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </section>

            <!-- C. Backup Operasional Widget (Owner/Superadmin) -->
            @if($exceptions['backup_health']['available'] && in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                @php
                    $backupCritical = $exceptions['backup_health']['severity'] === 'critical';
                @endphp
                <section class="ui-card space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Backup operasional</span>
                        <span class="ui-badge {{ $backupCritical ? 'ui-badge-rose' : 'ui-badge-emerald' }}">
                            {{ $backupCritical ? 'PERLU PERHATIAN' : 'SEHAT' }}
                        </span>
                    </div>

                    <p class="text-xs font-bold text-slate-800 leading-snug">
                        {{ $exceptions['backup_health']['message'] }}
                    </p>

                    @if($exceptions['backup_health']['last_successful_at'])
                        <p class="text-[11px] text-slate-500 font-semibold">
                            Berhasil terakhir: <span class="font-mono text-slate-700 font-bold">{{ $exceptions['backup_health']['last_successful_at']->format('d M Y H:i') }}</span>
                        </p>
                    @endif

                    @if(in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                        <a href="{{ route('admin.settings.backups.index') }}" class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold rounded-xl text-xs flex items-center justify-center gap-1 transition-colors">
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
