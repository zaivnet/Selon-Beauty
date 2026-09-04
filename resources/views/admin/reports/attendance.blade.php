@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Karyawan')
@section('page-title', 'Laporan Kehadiran & Rekapitukasi')

@section('content')
@php
    $authorizedOutlets = $authorizedOutlets ?? collect();
@endphp
<div class="space-y-6">

    <nav class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xs transition-colors" aria-label="Jenis laporan">
        <a href="{{ route('admin.reports.attendance') }}" aria-current="page" class="flex min-h-[44px] shrink-0 items-center rounded-lg bg-slate-900 dark:bg-rose-600 px-4 text-xs font-extrabold text-white shadow-sm ui-btn ui-btn-primary">Laporan Kehadiran</a>
        <a href="{{ route('admin.monthly-recaps.index') }}" class="flex min-h-[44px] shrink-0 items-center rounded-lg px-4 text-xs font-extrabold text-slate-500 dark:text-slate-400 transition hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200">Rekap Bulanan</a>
    </nav>

    <!-- Header Card with Export Actions -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        <div>
            <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100 tracking-tight">Laporan Kehadiran Karyawan</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
                Rekapitulasi kehadiran, keterlambatan, izin, sakit, cuti, dan lembur disetujui untuk operasional.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <!-- Print View Button -->
            <a href="{{ route('admin.reports.attendance.print', request()->all()) }}" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Print View</span>
            </a>

            <!-- CSV Export Button -->
            <a href="{{ route('admin.reports.attendance.export-csv', request()->all()) }}" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md shadow-emerald-600/20 transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors"
         x-data="{ isSubmitting: false }">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Filter Laporan</span>
                <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 animate-pulse ml-2">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Memuat laporan...
                </span>
            </div>
            @if(request()->hasAny(['start_date', 'end_date', 'outlet_id', 'employee_id', 'status', 'from', 'to', 'from_date', 'to_date']))
                <a href="{{ route('admin.reports.attendance') }}" class="text-[11px] font-bold text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors flex items-center gap-1">
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
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Dari Tanggal</label>
                <x-date-input name="start_date" value="{{ $filters['start_date'] }}" onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();" />
            </div>

            <!-- End Date -->
            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <x-date-input name="end_date" value="{{ $filters['end_date'] }}" onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();" />
            </div>

            <!-- Outlet -->
            @if(app(\App\Services\OutletModeService::class)->isMultiOutlet())
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Outlet</label>
                    <select name="outlet_id"
                            onchange="const empEl = this.form.querySelector('select[name=employee_id]'); if(empEl) empEl.value=''; this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                            class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
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
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Karyawan</label>
                <select name="employee_id"
                        onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                        class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
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
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Kehadiran</label>
                <select name="status"
                        onchange="this.form.dispatchEvent(new Event('submit')); this.form.submit();"
                        class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px] ui-select">
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
    @php
        $gSum = $reportData['global_summary'];
        $wHours = (int) floor($gSum['total_worked_minutes'] / 60);
        $wMins = $gSum['total_worked_minutes'] % 60;

        $oHours = (int) floor($gSum['total_approved_overtime_minutes'] / 60);
        $oMins = $gSum['total_approved_overtime_minutes'] % 60;
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3">
        <!-- Scheduled Work Days -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Hari Kerja</span>
            <div class="text-xl font-black text-slate-900 dark:text-slate-100">{{ $gSum['scheduled_work_days'] }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Hari</span></div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-amber-700 dark:text-amber-400 uppercase tracking-wider block">Libur</span>
            <div class="text-xl font-black text-amber-900 dark:text-amber-300">{{ $gSum['holiday_count'] ?? 0 }} <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Di luar denominator</p>
        </div>

        <div class="bg-slate-900 dark:bg-slate-800 p-4 rounded-2xl border border-slate-800 dark:border-slate-700 shadow-xs space-y-1 text-white">
            <span class="text-[10px] font-extrabold text-slate-300 dark:text-slate-400 uppercase tracking-wider block">Tingkat Hadir</span>
            <div class="text-xl font-black">{{ number_format((float) ($gSum['attendance_rate'] ?? 0), 1) }}%</div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Hanya hari kerja wajib</p>
        </div>

        <!-- Present -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Hadir</span>
            <div class="text-xl font-black text-emerald-800 dark:text-emerald-300">{{ $gSum['present_count'] }} <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Terlambat: {{ $gSum['late_count'] }} hari</p>
        </div>

        <!-- Absent -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Tidak Hadir</span>
            <div class="text-xl font-black text-rose-800 dark:text-rose-300">{{ $gSum['absent_count'] }} <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">Hari</span></div>
        </div>

        <!-- Permission / Sick / Leave -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Izin / Sakit / Cuti</span>
            <div class="text-xl font-black text-indigo-900 dark:text-indigo-300">{{ $gSum['permission_count'] + $gSum['sick_count'] + $gSum['leave_count'] }} <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Hari</span></div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">I:{{ $gSum['permission_count'] }} | S:{{ $gSum['sick_count'] }} | C:{{ $gSum['leave_count'] }}</p>
        </div>

        <!-- Total Worked Time -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Jam Kerja</span>
            <div class="text-lg font-black text-blue-900 dark:text-blue-300 font-mono">{{ $wHours }}j {{ $wMins }}m</div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Late: {{ $gSum['total_late_minutes'] }}m</p>
        </div>

        <!-- Approved Overtime -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-pink-600 dark:text-pink-400 uppercase tracking-wider block">Approved Lembur</span>
            <div class="text-lg font-black text-pink-900 dark:text-pink-300 font-mono">{{ $oHours }}j {{ $oMins }}m</div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Approved minutes</p>
        </div>
    </div>

    <!-- Summary per Employee Accordion / Cards -->
    @if(count($reportData['employee_summaries']) > 0)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between">
                <h3 class="text-xs font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-wider">Ringkasan Per Karyawan (Periode Terpilih)</h3>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ count($reportData['employee_summaries']) }} Karyawan</span>
            </div>

            <div class="overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px]">
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
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-slate-100">
                                    {{ $es['employee']->full_name }}
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">{{ $es['employee']->employee_code }} • {{ $es['employee']->jobTitle?->name ?? 'Karyawan' }}</span>
                                    @if(!empty($es['notice']))
                                        <span class="mt-1 block text-[10px] font-extrabold text-indigo-600 dark:text-indigo-400">⚡ {{ $es['notice'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $es['scheduled_work_days'] }} hari</td>
                                <td class="px-4 py-3 font-semibold text-amber-800 dark:text-amber-400">{{ $es['holiday_count'] ?? 0 }} hari</td>
                                <td class="px-4 py-3 font-black text-slate-900 dark:text-slate-100">{{ number_format((float) ($es['attendance_rate'] ?? 0), 1) }}%</td>
                                <td class="px-4 py-3 font-extrabold text-emerald-700 dark:text-emerald-400">{{ $es['present_count'] }} hari</td>
                                <td class="px-4 py-3 font-bold text-rose-700 dark:text-rose-400">{{ $es['late_count'] }} hari</td>
                                <td class="px-4 py-3 font-extrabold text-rose-900 dark:text-rose-300">{{ $es['absent_count'] }} hari</td>
                                <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">
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
        </div>
    @endif

    <!-- Detail Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Detail Rincian Harian</h3>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">
                {{ $paginatedRows->total() }} Total Record
            </span>
        </div>

        @if($paginatedRows->count() === 0)
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Belum ada data kehadiran pada periode yang dipilih.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Coba ubah filter tanggal atau karyawan di atas.</p>
            </div>
        @else
            <div class="overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
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
                                        <span class="mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">PENUGASAN OUTLET</span>
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
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $checkInAt ? $checkInAt->format('H:i') : '-' }}
                                </td>

                                <!-- Jam Pulang -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}
                                    @if(($row['checkout_source'] ?? $row['attendance']?->checkout_source) === 'auto_shift_end')
                                        <span class="block text-[9px] font-extrabold text-indigo-600 dark:text-indigo-400 font-sans">Auto</span>
                                    @endif
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $row['status_badge_class'] }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>

                                <!-- Terlambat -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['late_minutes'] > 0)
                                        <span class="font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800/60">{{ $row['late_minutes'] }}m</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">0m</span>
                                    @endif
                                </td>

                                <!-- Pulang Cepat -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['early_leave_minutes'] > 0)
                                        <span class="font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/60">{{ $row['early_leave_minutes'] }}m</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">0m</span>
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

            <!-- Pagination Container -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                {{ $paginatedRows->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
