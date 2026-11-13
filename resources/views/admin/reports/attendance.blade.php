@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Karyawan')
@section('page-title', 'Laporan Kehadiran & Rekapitulasi')

@section('content')
@php
    $authorizedOutlets = $authorizedOutlets ?? collect();
    $gSum = $reportData['global_summary'];
    $wHours = (int) floor($gSum['total_worked_minutes'] / 60);
    $wMins = $gSum['total_worked_minutes'] % 60;
    $oHours = (int) floor($gSum['total_approved_overtime_minutes'] / 60);
    $oMins = $gSum['total_approved_overtime_minutes'] % 60;
    $hasFilters = request()->hasAny(['start_date', 'end_date', 'outlet_id', 'employee_id', 'status', 'from', 'to', 'from_date', 'to_date']);
@endphp

<div class="space-y-6" x-data="{ isExporting: false }">

    <!-- Sub-navigation Tabs -->
    <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xs transition-colors" aria-label="Jenis laporan">
        <a href="{{ route('admin.reports.attendance') }}" aria-current="page" class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-xl bg-slate-900 dark:bg-rose-600 px-4 text-xs font-black text-white shadow-xs ui-btn ui-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Laporan Kehadiran</span>
        </a>
        <a href="{{ route('admin.monthly-recaps.index') }}" class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-xl px-4 text-xs font-extrabold text-slate-500 dark:text-slate-400 transition hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Rekap Bulanan</span>
        </a>
    </nav>

    <!-- Header Card with Export & Print Actions -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Audit Presensi
                </span>
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                    Periode {{ \Carbon\Carbon::parse($filters['start_date'])->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($filters['end_date'])->translatedFormat('d M Y') }}
                </span>
            </div>
            <h2 class="text-lg sm:text-xl font-black text-slate-950 dark:text-slate-100 tracking-tight">Laporan Kehadiran Karyawan</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Rekapitulasi kehadiran, keterlambatan, izin, sakit, cuti, dan lembur disetujui untuk operasional.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
            <!-- Print View Button -->
            <a href="{{ route('admin.reports.attendance.print', request()->all()) }}" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-2 cursor-pointer min-h-[44px] shadow-2xs border border-slate-200/80 dark:border-slate-700">
                <svg class="w-4 h-4 text-slate-600 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Print View</span>
            </a>

            <!-- CSV Export Button with Download Feedback -->
            <a href="{{ route('admin.reports.attendance.export-csv', request()->all()) }}"
               @click="isExporting = true; setTimeout(() => isExporting = false, 4000)"
               class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-extrabold rounded-xl shadow-xs shadow-emerald-600/20 transition-all text-xs flex items-center justify-center gap-2 cursor-pointer min-h-[44px]">
                <template x-if="!isExporting">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </template>
                <template x-if="isExporting">
                    <svg class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </template>
                <span x-text="isExporting ? 'Mengunduh...' : 'Export CSV'">Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors"
         x-data="{ isSubmitting: false }">
        <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </span>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">Filter Laporan</span>
                <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 animate-pulse ml-2">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Memuat laporan...
                </span>
            </div>
            @if($hasFilters)
                <a href="{{ route('admin.reports.attendance') }}" class="text-[11px] font-extrabold text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 transition-colors flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Bersihkan filter</span>
                </a>
            @endif
        </div>

        <form id="attendanceReportFilterForm" action="{{ route('admin.reports.attendance') }}" method="GET"
              class="grid grid-cols-1 sm:grid-cols-2 {{ app(\App\Services\OutletModeService::class)->isMultiOutlet() ? 'md:grid-cols-3 lg:grid-cols-5' : 'md:grid-cols-4' }} gap-3 text-xs"
              @submit="isSubmitting = true">
            <!-- Start Date -->
            <div class="w-full min-w-0 max-w-full">
                <label for="filter-start-date" class="block text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <x-date-input id="filter-start-date" name="start_date" value="{{ $filters['start_date'] }}" onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();" />
            </div>

            <!-- End Date -->
            <div class="w-full min-w-0 max-w-full">
                <label for="filter-end-date" class="block text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                <x-date-input id="filter-end-date" name="end_date" value="{{ $filters['end_date'] }}" onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();" />
            </div>

            <!-- Outlet -->
            @if(app(\App\Services\OutletModeService::class)->isMultiOutlet())
                <div>
                    <label for="filter-outlet" class="block text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Outlet</label>
                    <select id="filter-outlet" name="outlet_id"
                            onchange="const empEl = this.form.querySelector('select[name=employee_id]'); if(empEl) empEl.value=''; this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                            class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
                        <option value="">Semua Outlet</option>
                        @foreach($authorizedOutlets as $outletOption)
                            <option value="{{ $outletOption->id }}" {{ (isset($filters['outlet_id']) && (int) $filters['outlet_id'] === (int) $outletOption->id) ? 'selected' : '' }}>
                                {{ $outletOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Employee -->
            <div>
                <label for="filter-employee" class="block text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Karyawan</label>
                <select id="filter-employee" name="employee_id"
                        onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                        class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ (isset($filters['employee_id']) && (int) $filters['employee_id'] === (int) $emp->id) ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="filter-status" class="block text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Kehadiran</label>
                <select id="filter-status" name="status"
                        onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                        class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="present" {{ $filters['status'] === 'present' ? 'selected' : '' }}>Hadir (Termasuk Terlambat)</option>
                    <option value="late" {{ $filters['status'] === 'late' ? 'selected' : '' }}>Terlambat</option>
                    <option value="permission" {{ $filters['status'] === 'permission' ? 'selected' : '' }}>Izin</option>
                    <option value="sick" {{ $filters['status'] === 'sick' ? 'selected' : '' }}>Sakit</option>
                    <option value="leave" {{ $filters['status'] === 'leave' ? 'selected' : '' }}>Cuti</option>
                    <option value="absent" {{ $filters['status'] === 'absent' ? 'selected' : '' }}>Tidak Hadir (Alpa)</option>
                    <option value="off" {{ $filters['status'] === 'off' ? 'selected' : '' }}>OFF Pekanan</option>
                    <option value="holiday" {{ $filters['status'] === 'holiday' ? 'selected' : '' }}>Libur</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Summary Metrics Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3">
        <!-- Scheduled Work Days -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Hari Kerja</span>
            <div class="text-xl font-black text-slate-950 dark:text-slate-100 font-mono">{{ $gSum['scheduled_work_days'] }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Hari</span></div>
        </div>

        <!-- Holiday Days -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-wider block">Libur</span>
            <div class="text-xl font-black text-amber-900 dark:text-amber-300 font-mono">{{ $gSum['holiday_count'] ?? 0 }} <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Di luar denominator</p>
        </div>

        <!-- Attendance Rate -->
        <div class="bg-slate-900 dark:bg-rose-950/40 p-4 rounded-2xl border border-slate-800 dark:border-rose-800/60 shadow-xs space-y-1 text-white transition-colors">
            <span class="text-[10px] font-black text-slate-300 dark:text-rose-300 uppercase tracking-wider block">Tingkat Hadir</span>
            <div class="text-xl font-black font-mono text-white dark:text-rose-100">{{ number_format((float) ($gSum['attendance_rate'] ?? 0), 1) }}%</div>
            <p class="text-[10px] text-slate-400 dark:text-rose-300/80">Hanya hari kerja wajib</p>
        </div>

        <!-- Present Days -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Hadir</span>
            <div class="text-xl font-black text-emerald-800 dark:text-emerald-300 font-mono">{{ $gSum['present_count'] }} <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Terlambat: {{ $gSum['late_count'] }} hari</p>
        </div>

        <!-- Absent Days -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-rose-200 dark:border-rose-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Tidak Hadir</span>
            <div class="text-xl font-black text-rose-800 dark:text-rose-300 font-mono">{{ $gSum['absent_count'] }} <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">Hari</span></div>
        </div>

        <!-- Permission / Sick / Leave -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-indigo-200 dark:border-indigo-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Izin / Sakit / Cuti</span>
            <div class="text-xl font-black text-indigo-900 dark:text-indigo-300 font-mono">{{ $gSum['permission_count'] + $gSum['sick_count'] + $gSum['leave_count'] }} <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">I:{{ $gSum['permission_count'] }} | S:{{ $gSum['sick_count'] }} | C:{{ $gSum['leave_count'] }}</p>
        </div>

        <!-- Total Worked Time -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-blue-200 dark:border-blue-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Jam Kerja</span>
            <div class="text-lg font-black text-blue-900 dark:text-blue-300 font-mono">{{ $wHours }}j {{ $wMins }}m</div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Late: {{ $gSum['total_late_minutes'] }}m</p>
        </div>

        <!-- Approved Overtime -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-pink-200 dark:border-pink-800/60 shadow-xs space-y-1 transition-colors">
            <span class="text-[10px] font-black text-pink-600 dark:text-pink-400 uppercase tracking-wider block">Approved Lembur</span>
            <div class="text-lg font-black text-pink-900 dark:text-pink-300 font-mono">{{ $oHours }}j {{ $oMins }}m</div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Approved minutes</p>
        </div>
    </div>

    <!-- Summary per Employee Section -->
    @if(count($reportData['employee_summaries']) > 0)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-md bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                    <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">Ringkasan Per Karyawan (Periode Terpilih)</h3>
                </div>
                <span class="text-[11px] font-black text-slate-600 dark:text-slate-400 bg-slate-200/80 dark:bg-slate-800 px-2.5 py-0.5 rounded-full">{{ count($reportData['employee_summaries']) }} Karyawan</span>
            </div>

            <!-- Desktop Employee Summary Table -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="bg-slate-100/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-black text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Karyawan</th>
                            <th class="px-4 py-3">Hari Kerja</th>
                            <th class="px-4 py-3">Libur</th>
                            <th class="px-4 py-3">Rate</th>
                            <th class="px-4 py-3">Hadir</th>
                            <th class="px-4 py-3">Terlambat</th>
                            <th class="px-4 py-3">Tidak Hadir</th>
                            <th class="px-4 py-3">Izin / Sakit / Cuti</th>
                            <th class="px-4 py-3">Total Late</th>
                            <th class="px-4 py-3">Total Worked</th>
                            <th class="px-4 py-3">Approved Overtime</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($reportData['employee_summaries'] as $empId => $es)
                            @php
                                $eWHours = (int) floor($es['total_worked_minutes'] / 60);
                                $eWMins = $es['total_worked_minutes'] % 60;
                                $eOHours = (int) floor($es['total_approved_overtime_minutes'] / 60);
                                $eOMins = $es['total_approved_overtime_minutes'] % 60;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-slate-100">
                                    {{ $es['employee']->full_name }}
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">{{ $es['employee']->employee_code }} • {{ $es['employee']->jobTitle?->name ?? 'Karyawan' }}</span>
                                    @if(!empty($es['notice']))
                                        <span class="mt-1 block text-[10px] font-black text-indigo-600 dark:text-indigo-400">⚡ {{ $es['notice'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $es['scheduled_work_days'] }} hari</td>
                                <td class="px-4 py-3 font-mono font-semibold text-amber-800 dark:text-amber-400">{{ $es['holiday_count'] ?? 0 }} hari</td>
                                <td class="px-4 py-3 font-mono font-black text-slate-900 dark:text-slate-100">{{ number_format((float) ($es['attendance_rate'] ?? 0), 1) }}%</td>
                                <td class="px-4 py-3 font-mono font-extrabold text-emerald-700 dark:text-emerald-400">{{ $es['present_count'] }} hari</td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-700 dark:text-rose-400">{{ $es['late_count'] }} hari</td>
                                <td class="px-4 py-3 font-mono font-extrabold text-rose-900 dark:text-rose-300">{{ $es['absent_count'] }} hari</td>
                                <td class="px-4 py-3 font-mono font-semibold text-slate-700 dark:text-slate-300">
                                    I: {{ $es['permission_count'] }} | S: {{ $es['sick_count'] }} | C: {{ $es['leave_count'] }}
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-700 dark:text-rose-400">{{ $es['total_late_minutes'] }}m</td>
                                <td class="px-4 py-3 font-mono font-bold text-blue-800 dark:text-blue-300">{{ $eWHours }}j {{ $eWMins }}m</td>
                                <td class="px-4 py-3 font-mono font-extrabold text-pink-800 dark:text-pink-300">{{ $eOHours }}j {{ $eOMins }}m</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Employee Summary Cards (md:hidden) -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach($reportData['employee_summaries'] as $empId => $es)
                    @php
                        $eWHours = (int) floor($es['total_worked_minutes'] / 60);
                        $eWMins = $es['total_worked_minutes'] % 60;
                        $eOHours = (int) floor($es['total_approved_overtime_minutes'] / 60);
                        $eOMins = $es['total_approved_overtime_minutes'] % 60;
                    @endphp
                    <article class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h4 class="text-sm font-black text-slate-900 dark:text-slate-100 truncate">{{ $es['employee']->full_name }}</h4>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $es['employee']->employee_code }} • {{ $es['employee']->jobTitle?->name ?? 'Karyawan' }}</p>
                                @if(!empty($es['notice']))
                                    <span class="mt-1 inline-block text-[10px] font-black text-indigo-600 dark:text-indigo-400">⚡ {{ $es['notice'] }}</span>
                                @endif
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-900 text-white dark:bg-rose-600">
                                {{ number_format((float) ($es['attendance_rate'] ?? 0), 1) }}%
                            </span>
                        </div>

                        <dl class="grid grid-cols-4 gap-1.5 text-center text-[10px]">
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl">
                                <dt class="text-slate-500 dark:text-slate-400">Kerja</dt>
                                <dd class="font-mono font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ $es['scheduled_work_days'] }}h</dd>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-xl">
                                <dt class="text-emerald-700 dark:text-emerald-400">Hadir</dt>
                                <dd class="font-mono font-bold text-emerald-800 dark:text-emerald-300 mt-0.5">{{ $es['present_count'] }}h</dd>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-950/40 p-2 rounded-xl">
                                <dt class="text-amber-700 dark:text-amber-400">Telat</dt>
                                <dd class="font-mono font-bold text-amber-800 dark:text-amber-300 mt-0.5">{{ $es['late_count'] }}h</dd>
                            </div>
                            <div class="bg-rose-50 dark:bg-rose-950/40 p-2 rounded-xl">
                                <dt class="text-rose-700 dark:text-rose-400">Alpa</dt>
                                <dd class="font-mono font-bold text-rose-800 dark:text-rose-300 mt-0.5">{{ $es['absent_count'] }}h</dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[10px] text-slate-600 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                            <span>I/S/C: <b class="font-mono text-indigo-700 dark:text-indigo-400">{{ $es['permission_count'] }}/{{ $es['sick_count'] }}/{{ $es['leave_count'] }}</b></span>
                            <span>Kerja: <b class="font-mono text-blue-700 dark:text-blue-400">{{ $eWHours }}j {{ $eWMins }}m</b></span>
                            <span>Lembur: <b class="font-mono text-pink-700 dark:text-pink-400">{{ $eOHours }}j {{ $eOMins }}m</b></span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Detail Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </span>
                <h3 class="text-sm font-black text-slate-950 dark:text-slate-100">Detail Rincian Harian</h3>
            </div>
            <span class="text-xs font-black text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200/60 dark:border-slate-700">
                {{ $paginatedRows->total() }} Total Record
            </span>
        </div>

        @if($paginatedRows->count() === 0)
            <div class="text-center py-12 px-4">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm font-black text-slate-800 dark:text-slate-200">Belum ada data kehadiran pada periode yang dipilih.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Coba ubah filter tanggal atau karyawan di atas.</p>
            </div>
        @else
            <!-- Desktop Detail Table (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-black text-[10px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">Karyawan</th>
                            <th class="px-4 py-3.5">Outlet Kerja</th>
                            <th class="px-4 py-3.5">Shift / Jadwal</th>
                            <th class="px-4 py-3.5">Jam Masuk</th>
                            <th class="px-4 py-3.5">Jam Pulang</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Terlambat</th>
                            <th class="px-4 py-3.5">Pulang Cepat</th>
                            <th class="px-4 py-3.5">Worked Time</th>
                            <th class="px-4 py-3.5">Approved Lembur</th>
                            <th class="px-4 py-3.5">Actual Lembur</th>
                            <th class="px-4 py-3.5">Credited Lembur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($paginatedRows as $row)
                            @php
                                $rWHours = (int) floor($row['worked_minutes'] / 60);
                                $rWMins = $row['worked_minutes'] % 60;
                                $rowDate = isset($row['date']) && $row['date']
                                    ? ($row['date'] instanceof \Carbon\CarbonInterface ? $row['date'] : \Carbon\Carbon::parse($row['date']))
                                    : null;
                                $checkInAt = isset($row['check_in_at']) && $row['check_in_at']
                                    ? ($row['check_in_at'] instanceof \Carbon\CarbonInterface ? $row['check_in_at'] : \Carbon\Carbon::parse($row['check_in_at']))
                                    : null;
                                $checkOutAt = isset($row['check_out_at']) && $row['check_out_at']
                                    ? ($row['check_out_at'] instanceof \Carbon\CarbonInterface ? $row['check_out_at'] : \Carbon\Carbon::parse($row['check_out_at']))
                                    : null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <!-- Tanggal -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ $rowDate ? $rowDate->translatedFormat('d/m/Y') : '-' }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ $rowDate ? $rowDate->translatedFormat('l') : '-' }}</div>
                                </td>

                                <!-- Karyawan -->
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ $row['employee']->full_name }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ $row['employee']->employee_code }} • {{ $row['employee']->jobTitle?->name ?? 'Karyawan' }}</div>
                                </td>

                                <!-- Outlet Kerja -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $row['work_outlet']?->name ?? '-' }}</div>
                                    @if(!empty($row['is_temporary_assignment']))
                                        <span class="mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">PENUGASAN OUTLET</span>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Home: {{ $row['historical_home_outlet']?->name ?? '-' }}</div>
                                    @endif
                                </td>

                                <!-- Shift -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['shift'])
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $row['shift']->name }}</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ substr($row['shift']->start_time, 0, 5) }} - {{ substr($row['shift']->end_time, 0, 5) }}</div>
                                    @else
                                        @if(in_array($row['effective_schedule']['source'] ?? null, ['public_holiday', 'company_holiday'], true))
                                            <span class="font-black text-amber-800 dark:text-amber-400">LIBUR</span><span class="mt-0.5 block max-w-36 truncate text-[10px] text-amber-700 dark:text-amber-400/80">{{ $row['effective_schedule']['holiday_name'] ?? '' }}</span>
                                        @else
                                            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ $row['effective_schedule']['label'] ?? ($row['schedule']?->schedule_type ? strtoupper($row['schedule']->schedule_type) : '-') }}</span>
                                        @endif
                                    @endif
                                </td>

                                <!-- Jam Masuk -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $checkInAt ? $checkInAt->format('H:i') : '-' }}
                                </td>

                                <!-- Jam Pulang -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}
                                    @if(($row['checkout_source'] ?? $row['attendance']?->checkout_source) === 'auto_shift_end')
                                        <span class="block text-[9px] font-black text-indigo-600 dark:text-indigo-400 font-sans">Auto</span>
                                    @endif
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black border {{ $row['status_badge_class'] }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>

                                <!-- Terlambat -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['late_minutes'] > 0)
                                        <span class="font-mono font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800/60">{{ $row['late_minutes'] }}m</span>
                                    @else
                                        <span class="font-mono text-slate-400 dark:text-slate-500">0m</span>
                                    @endif
                                </td>

                                <!-- Pulang Cepat -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['early_leave_minutes'] > 0)
                                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/60">{{ $row['early_leave_minutes'] }}m</span>
                                    @else
                                        <span class="font-mono text-slate-400 dark:text-slate-500">0m</span>
                                    @endif
                                </td>

                                <!-- Worked Time -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800 dark:text-slate-200">
                                    @if($row['worked_minutes'] > 0)
                                        {{ $rWHours }}j {{ $rWMins }}m
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 font-normal">0m</span>
                                    @endif
                                </td>

                                <!-- Approved Overtime -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono">
                                    @if($row['approved_overtime_minutes'] > 0)
                                        <span class="font-black text-pink-700 dark:text-pink-300 bg-pink-50 dark:bg-pink-950/60 px-2 py-0.5 rounded border border-pink-200 dark:border-pink-800/60">
                                            {{ $row['approved_overtime_minutes'] }}m
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">0m</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ $row['actual_overtime_minutes'] ?? 0 }}m</td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-violet-700 dark:text-violet-400">{{ $row['credited_overtime_minutes'] ?? 0 }}m</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Detail Cards (md:hidden) -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach($paginatedRows as $row)
                    @php
                        $rWHours = (int) floor($row['worked_minutes'] / 60);
                        $rWMins = $row['worked_minutes'] % 60;
                        $rowDate = isset($row['date']) && $row['date']
                            ? ($row['date'] instanceof \Carbon\CarbonInterface ? $row['date'] : \Carbon\Carbon::parse($row['date']))
                            : null;
                        $checkInAt = isset($row['check_in_at']) && $row['check_in_at']
                            ? ($row['check_in_at'] instanceof \Carbon\CarbonInterface ? $row['check_in_at'] : \Carbon\Carbon::parse($row['check_in_at']))
                            : null;
                        $checkOutAt = isset($row['check_out_at']) && $row['check_out_at']
                            ? ($row['check_out_at'] instanceof \Carbon\CarbonInterface ? $row['check_out_at'] : \Carbon\Carbon::parse($row['check_out_at']))
                            : null;
                    @endphp
                    <article class="p-4 space-y-3">
                        <!-- Top Row: Date & Status Badge -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-black text-slate-900 dark:text-slate-100 text-xs">{{ $rowDate ? $rowDate->translatedFormat('d/m/Y') : '-' }} · {{ $rowDate ? $rowDate->translatedFormat('l') : '-' }}</div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $row['employee']->full_name }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $row['employee']->employee_code }} • {{ $row['employee']->jobTitle?->name ?? 'Karyawan' }}</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black border {{ $row['status_badge_class'] }}">
                                {{ $row['status_label'] }}
                            </span>
                        </div>

                        <!-- Outlet & Shift Info -->
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2.5 text-[11px] space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400 text-[10px]">Outlet Kerja:</span>
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ $row['work_outlet']?->name ?? '-' }}</span>
                            </div>
                            @if(!empty($row['is_temporary_assignment']))
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="px-1.5 py-0.2 rounded font-black bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">PENUGASAN OUTLET</span>
                                    <span class="text-slate-400 dark:text-slate-500">Home: {{ $row['historical_home_outlet']?->name ?? '-' }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between border-t border-slate-200/60 dark:border-slate-700/60 pt-1">
                                <span class="text-slate-500 dark:text-slate-400 text-[10px]">Jadwal / Shift:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    @if($row['shift'])
                                        {{ $row['shift']->name }} ({{ substr($row['shift']->start_time, 0, 5) }}-{{ substr($row['shift']->end_time, 0, 5) }})
                                    @else
                                        @if(in_array($row['effective_schedule']['source'] ?? null, ['public_holiday', 'company_holiday'], true))
                                            <span class="text-amber-800 dark:text-amber-400 font-black">LIBUR</span>
                                        @else
                                            {{ $row['effective_schedule']['label'] ?? '-' }}
                                        @endif
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Time Stamps Grid -->
                        <dl class="grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-200 dark:bg-slate-800 text-center">
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Masuk</dt>
                                <dd class="mt-0.5 font-mono text-xs font-black text-slate-900 dark:text-slate-100">
                                    {{ $checkInAt ? $checkInAt->format('H:i') : '-' }}
                                </dd>
                            </div>
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Pulang</dt>
                                <dd class="mt-0.5 font-mono text-xs font-black text-slate-900 dark:text-slate-100">
                                    {{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}
                                    @if(($row['checkout_source'] ?? $row['attendance']?->checkout_source) === 'auto_shift_end')
                                        <span class="block text-[8px] font-black text-indigo-600 dark:text-indigo-400 font-sans">Auto</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Worked</dt>
                                <dd class="mt-0.5 font-mono text-xs font-black text-blue-700 dark:text-blue-400">
                                    {{ $row['worked_minutes'] > 0 ? $rWHours.'j '.$rWMins.'m' : '0m' }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Highlights / Badges -->
                        <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                            @if($row['late_minutes'] > 0)
                                <span class="font-mono font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800/60">
                                    Late {{ $row['late_minutes'] }}m
                                </span>
                            @endif
                            @if($row['early_leave_minutes'] > 0)
                                <span class="font-mono font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/60">
                                    Pulang Cepat {{ $row['early_leave_minutes'] }}m
                                </span>
                            @endif
                            @if($row['approved_overtime_minutes'] > 0)
                                <span class="font-mono font-bold text-pink-700 dark:text-pink-300 bg-pink-50 dark:bg-pink-950/60 px-2 py-0.5 rounded border border-pink-200 dark:border-pink-800/60">
                                    OT Apprv {{ $row['approved_overtime_minutes'] }}m
                                </span>
                            @endif
                            @if(($row['credited_overtime_minutes'] ?? 0) > 0)
                                <span class="font-mono font-bold text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-2 py-0.5 rounded border border-violet-200 dark:border-violet-800/60">
                                    OT Credit {{ $row['credited_overtime_minutes'] }}m
                                </span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination Container -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                {{ $paginatedRows->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
