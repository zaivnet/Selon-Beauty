@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Karyawan')
@section('page-title', 'Laporan Kehadiran & Rekapitukasi')

@section('content')
<div class="space-y-6">

    <!-- Header Card with Export Actions -->
    <div class="bg-white p-5 md:p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Laporan Kehadiran Karyawan</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Rekapitulasi kehadiran, keterlambatan, izin, sakit, cuti, dan lembur disetujui untuk operasional.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <!-- Print View Button -->
            <a href="{{ route('admin.reports.attendance.print', request()->all()) }}" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
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
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.reports.attendance') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <!-- Start Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
            </div>

            <!-- Employee -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Karyawan</label>
                <select name="employee_id" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status Kehadiran</label>
                <select name="status" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="present" {{ $filters['status'] === 'present' ? 'selected' : '' }}>Hadir (Termasuk Terlambat)</option>
                    <option value="late" {{ $filters['status'] === 'late' ? 'selected' : '' }}>Terlambat</option>
                    <option value="permission" {{ $filters['status'] === 'permission' ? 'selected' : '' }}>Izin</option>
                    <option value="sick" {{ $filters['status'] === 'sick' ? 'selected' : '' }}>Sakit</option>
                    <option value="leave" {{ $filters['status'] === 'leave' ? 'selected' : '' }}>Cuti</option>
                    <option value="absent" {{ $filters['status'] === 'absent' ? 'selected' : '' }}>Tidak Hadir (Alpa)</option>
                    <option value="off" {{ $filters['status'] === 'off' ? 'selected' : '' }}>OFF Pekanan</option>
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.reports.attendance') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center cursor-pointer min-h-[44px]">
                    Reset
                </a>
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
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Scheduled Work Days -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Hari Kerja</span>
            <div class="text-xl font-black text-slate-900">{{ $gSum['scheduled_work_days'] }} <span class="text-xs font-semibold text-slate-500">Hari</span></div>
        </div>

        <!-- Present -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-wider block">Hadir</span>
            <div class="text-xl font-black text-emerald-800">{{ $gSum['present_count'] }} <span class="text-xs font-semibold text-emerald-600">Hari</span></div>
            <p class="text-[10px] text-slate-400">Terlambat: {{ $gSum['late_count'] }} hari</p>
        </div>

        <!-- Absent -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-rose-600 uppercase tracking-wider block">Tidak Hadir</span>
            <div class="text-xl font-black text-rose-800">{{ $gSum['absent_count'] }} <span class="text-xs font-semibold text-rose-600">Hari</span></div>
        </div>

        <!-- Permission / Sick / Leave -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-indigo-600 uppercase tracking-wider block">Izin / Sakit / Cuti</span>
            <div class="text-xl font-black text-indigo-900">{{ $gSum['permission_count'] + $gSum['sick_count'] + $gSum['leave_count'] }} <span class="text-xs font-semibold text-indigo-600">Hari</span></div>
            <p class="text-[10px] text-slate-400">I:{{ $gSum['permission_count'] }} | S:{{ $gSum['sick_count'] }} | C:{{ $gSum['leave_count'] }}</p>
        </div>

        <!-- Total Worked Time -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider block">Jam Kerja</span>
            <div class="text-lg font-black text-blue-900 font-mono">{{ $wHours }}j {{ $wMins }}m</div>
            <p class="text-[10px] text-slate-400">Late: {{ $gSum['total_late_minutes'] }}m</p>
        </div>

        <!-- Approved Overtime -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-extrabold text-pink-600 uppercase tracking-wider block">Approved Lembur</span>
            <div class="text-lg font-black text-pink-900 font-mono">{{ $oHours }}j {{ $oMins }}m</div>
            <p class="text-[10px] text-slate-400">Approved minutes</p>
        </div>
    </div>

    <!-- Summary per Employee Accordion / Cards -->
    @if(count($reportData['employee_summaries']) > 0)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Ringkasan Per Karyawan (Periode Terpilih)</h3>
                <span class="text-[11px] font-bold text-slate-500">{{ count($reportData['employee_summaries']) }} Karyawan</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider font-extrabold text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Karyawan</th>
                            <th class="px-4 py-3">Hari Kerja</th>
                            <th class="px-4 py-3">Hadir</th>
                            <th class="px-4 py-3">Terlambat</th>
                            <th class="px-4 py-3">Tidak Hadir</th>
                            <th class="px-4 py-3">Izin / Sakit / Cuti</th>
                            <th class="px-4 py-3">Total Late</th>
                            <th class="px-4 py-3">Total Worked</th>
                            <th class="px-4 py-3">Approved Overtime</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($reportData['employee_summaries'] as $empId => $es)
                            @php
                                $eWHours = (int) floor($es['total_worked_minutes'] / 60);
                                $eWMins = $es['total_worked_minutes'] % 60;
                                $eOHours = (int) floor($es['total_approved_overtime_minutes'] / 60);
                                $eOMins = $es['total_approved_overtime_minutes'] % 60;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-900">
                                    {{ $es['employee']->full_name }}
                                    <span class="text-[10px] text-slate-500 block font-normal">{{ $es['employee']->employee_code }} • {{ $es['employee']->jobTitle?->name ?? 'Karyawan' }}</span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $es['scheduled_work_days'] }} hari</td>
                                <td class="px-4 py-3 font-extrabold text-emerald-700">{{ $es['present_count'] }} hari</td>
                                <td class="px-4 py-3 font-bold text-rose-700">{{ $es['late_count'] }} hari</td>
                                <td class="px-4 py-3 font-extrabold text-rose-900">{{ $es['absent_count'] }} hari</td>
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    I: {{ $es['permission_count'] }} | S: {{ $es['sick_count'] }} | C: {{ $es['leave_count'] }}
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-700">{{ $es['total_late_minutes'] }}m</td>
                                <td class="px-4 py-3 font-mono font-bold text-blue-800">{{ $eWHours }}j {{ $eWMins }}m</td>
                                <td class="px-4 py-3 font-mono font-extrabold text-pink-800">{{ $eOHours }}j {{ $eOMins }}m</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Detail Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900">Detail Rincian Harian</h3>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                {{ $paginatedRows->total() }} Total Record
            </span>
        </div>

        @if($paginatedRows->count() === 0)
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-sm font-bold text-slate-700">Belum ada data kehadiran pada periode yang dipilih.</p>
                <p class="text-xs text-slate-500 mt-1">Coba ubah filter tanggal atau karyawan di atas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">Karyawan</th>
                            <th class="px-4 py-3.5">Shift / Jadwal</th>
                            <th class="px-4 py-3.5">Jam Masuk</th>
                            <th class="px-4 py-3.5">Jam Pulang</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Terlambat</th>
                            <th class="px-4 py-3.5">Pulang Cepat</th>
                            <th class="px-4 py-3.5">Worked Time</th>
                            <th class="px-4 py-3.5">Approved Lembur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
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
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Tanggal -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $rowDate ? $rowDate->translatedFormat('d/m/Y') : '-' }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $rowDate ? $rowDate->translatedFormat('l') : '-' }}</div>
                                </td>

                                <!-- Karyawan -->
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900">{{ $row['employee']->full_name }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $row['employee']->employee_code }} • {{ $row['employee']->jobTitle?->name ?? 'Karyawan' }}</div>
                                </td>

                                <!-- Shift -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['shift'])
                                        <div class="font-bold text-slate-800">{{ $row['shift']->name }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium">{{ substr($row['shift']->start_time, 0, 5) }} - {{ substr($row['shift']->end_time, 0, 5) }}</div>
                                    @else
                                        <span class="text-slate-500 font-medium">{{ $row['schedule']?->schedule_type ? strtoupper($row['schedule']->schedule_type) : '-' }}</span>
                                    @endif
                                </td>

                                <!-- Jam Masuk -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold text-slate-800">
                                    {{ $checkInAt ? $checkInAt->format('H:i') : '-' }}
                                </td>

                                <!-- Jam Pulang -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold text-slate-800">
                                    {{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}
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
                                        <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">{{ $row['late_minutes'] }}m</span>
                                    @else
                                        <span class="text-slate-400">0m</span>
                                    @endif
                                </td>

                                <!-- Pulang Cepat -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($row['early_leave_minutes'] > 0)
                                        <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">{{ $row['early_leave_minutes'] }}m</span>
                                    @else
                                        <span class="text-slate-400">0m</span>
                                    @endif
                                </td>

                                <!-- Worked Time -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800">
                                    @if($row['worked_minutes'] > 0)
                                        {{ $rWHours }}j {{ $rWMins }}m
                                    @else
                                        <span class="text-slate-400 font-normal">0m</span>
                                    @endif
                                </td>

                                <!-- Approved Overtime -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono">
                                    @if($row['approved_overtime_minutes'] > 0)
                                        <span class="font-black text-pink-700 bg-pink-50 px-2 py-0.5 rounded border border-pink-200">
                                            {{ $row['approved_overtime_minutes'] }}m
                                        </span>
                                    @else
                                        <span class="text-slate-400">0m</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Container -->
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $paginatedRows->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
