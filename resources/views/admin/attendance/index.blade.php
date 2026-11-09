@extends('layouts.admin')

@section('title', 'Monitoring Absensi Karyawan')
@section('page-title', 'Monitoring Kehadiran Real-Time')

@section('content')
<div class="space-y-6">

    <!-- Header & Filter Bar Card -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Monitoring Absensi Karyawan</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Filter dan evaluasi rincian presensi harian, lokasi GPS, dan bukti foto selfie</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-outlet-filter />
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ $selectedDateFormatted }}</span>
                </span>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Tanggal</label>
                <x-date-input name="date" value="{{ $filters['date'] }}" wrapperClass="bg-white dark:bg-slate-950" />
            </div>

            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Karyawan</label>
                <select name="employee_id" class="w-full min-w-0 max-w-full box-border px-3 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 focus:outline-none min-h-[44px] ui-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Status Kehadiran</label>
                <select name="status" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 focus:outline-none min-h-[44px] ui-select">
                    <option value="">Semua Status</option>
                    <option value="present" {{ $filters['status'] === 'present' ? 'selected' : '' }}>Hadir / Tepat Waktu</option>
                    <option value="late" {{ $filters['status'] === 'late' ? 'selected' : '' }}>Terlambat</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Belum Check-In</option>
                    <option value="leave" {{ $filters['status'] === 'leave' ? 'selected' : '' }}>Izin / Sakit / Cuti</option>
                    <option value="off" {{ $filters['status'] === 'off' ? 'selected' : '' }}>OFF Pekanan</option>
                    <option value="holiday" {{ $filters['status'] === 'holiday' ? 'selected' : '' }}>Libur Toko</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px] ui-btn ui-btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter Data</span>
                </button>
                <a href="{{ route('admin.attendance.index') }}" class="py-2.5 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold rounded-xl transition-colors cursor-pointer min-h-[44px] flex items-center justify-center border border-slate-200 dark:border-slate-700">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Metrics for Selected Date -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden transition-all hover:shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider block">Total Karyawan</span>
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
            </div>
            <span class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">{{ $metrics['total_employees'] }}</span>
        </div>
        <div class="bg-emerald-50/70 dark:bg-emerald-950/40 p-4 rounded-2xl border border-emerald-200/80 dark:border-emerald-800/60 shadow-xs relative overflow-hidden transition-all hover:shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold uppercase tracking-wider block">Hadir</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <span class="text-2xl font-extrabold text-emerald-900 dark:text-emerald-300 tracking-tight">{{ $metrics['present_today'] }}</span>
        </div>
        <div class="bg-amber-50/70 dark:bg-amber-950/40 p-4 rounded-2xl border border-amber-200/80 dark:border-amber-800/60 shadow-xs relative overflow-hidden transition-all hover:shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] text-amber-700 dark:text-amber-400 font-bold uppercase tracking-wider block">Terlambat</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <span class="text-2xl font-extrabold text-amber-900 dark:text-amber-300 tracking-tight">{{ $metrics['late_today'] }}</span>
        </div>
        <div class="bg-rose-50/70 dark:bg-rose-950/40 p-4 rounded-2xl border border-rose-200/80 dark:border-rose-800/60 shadow-xs relative overflow-hidden transition-all hover:shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] text-rose-700 dark:text-rose-400 font-bold uppercase tracking-wider block">Belum Check-In</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            </div>
            <span class="text-2xl font-extrabold text-rose-900 dark:text-rose-300 tracking-tight">{{ $metrics['pending_check_in_today'] }}</span>
        </div>
        <div class="bg-sky-50/70 dark:bg-sky-950/40 p-4 rounded-2xl border border-sky-200/80 dark:border-sky-800/60 shadow-xs relative overflow-hidden transition-all hover:shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] text-sky-700 dark:text-sky-400 font-bold uppercase tracking-wider block">Izin / Sakit / Cuti</span>
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
            </div>
            <span class="text-2xl font-extrabold text-sky-900 dark:text-sky-300 tracking-tight">{{ $metrics['leave_today'] }}</span>
        </div>
    </div>

    <!-- Main Attendance Table / Cards -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 md:p-6 space-y-4 transition-colors">
        @if(count($attendanceItems) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-800/40">
                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak Ada Data Ditemukan</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">Tidak ada data kehadiran yang sesuai dengan filter yang Anda pilih.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left border-collapse ui-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/80 dark:bg-slate-800/60">
                            <th class="py-3 px-4 rounded-l-xl">Karyawan</th>
                            <th class="py-3 px-4">Shift</th>
                            <th class="py-3 px-4">Jam Masuk</th>
                            <th class="py-3 px-4">Jam Pulang</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Terlambat</th>
                            <th class="py-3 px-4">Lokasi Evidence</th>
                            <th class="py-3 px-4 text-right rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach($attendanceItems as $item)
                            @php
                                $emp = $item['employee'];
                                $sched = $item['schedule'];
                                $rec = $item['record'];
                                $effective = $item['effective_schedule'] ?? null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60 font-bold flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                            {{ substr($emp->full_name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-900 dark:text-slate-100">{{ $emp->full_name }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?? 'Staf Toko' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-medium">
                                    @if($effective && $effective['shift'])
                                        <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $effective['shift']->name }}</span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ substr($effective['shift']->start_time, 0, 5) }} - {{ substr($effective['shift']->end_time, 0, 5) }}</span>
                                        @if($effective['source'] === 'employee_override')<span class="mt-1 inline-block text-[8px] font-black text-indigo-700 dark:text-indigo-400">JADWAL KHUSUS</span>@elseif($effective['source'] === 'special_working_day')<span class="mt-1 inline-block text-[8px] font-black text-emerald-700 dark:text-emerald-400">HARI KERJA KHUSUS</span>@endif
                                    @elseif($effective && in_array($effective['source'], ['public_holiday', 'company_holiday'], true))
                                        <span class="font-black text-amber-800 dark:text-amber-400">LIBUR</span><span class="block max-w-32 truncate text-[10px] text-amber-700 dark:text-amber-300">{{ $effective['holiday_name'] }}</span>
                                    @elseif($effective && $effective['source'] === 'employee_override')
                                        <span class="font-black text-violet-800 dark:text-violet-400">LIBUR KHUSUS</span>
                                    @elseif($sched && $sched->shift)
                                        <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $sched->shift->name }}</span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ substr($sched->shift->start_time, 0, 5) }} - {{ substr($sched->shift->end_time, 0, 5) }}</span>
                                    @elseif($sched && $sched->schedule_type === 'off')
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold italic">OFF Pekanan</span>
                                    @elseif($sched && $sched->schedule_type === 'holiday')
                                        <span class="text-amber-700 dark:text-amber-400 font-semibold italic">Libur Toko</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 italic">Belum Ada Shift</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $rec?->check_in_at ? $rec->check_in_at->format('H:i') : '--:--' }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $rec?->check_out_at ? $rec->check_out_at->format('H:i') : '--:--' }}
                                    @if($rec && $rec->checkout_source === 'auto_shift_end')
                                        <span class="block text-[9px] font-extrabold text-indigo-600 dark:text-indigo-400 font-sans">Auto</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $item['badge_class'] }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    @if($rec && $rec->late_minutes > 0)
                                        <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $rec->late_minutes }}m</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">0m</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($rec && $rec->check_in_distance_meters !== null)
                                        <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">{{ $rec->getWorkOutletName() }}</span>
                                        @if(!empty($item['is_temporary_assignment']))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 my-0.5">PENUGASAN OUTLET</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium block">Home: {{ $item['historical_home_outlet']?->name ?? '-' }}</span>
                                        @endif
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium block">Jarak: {{ round($rec->check_in_distance_meters) }}m • Akurasi: ±{{ round($rec->check_in_accuracy_meters) }}m</span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($rec)
                                        @if($rec->is_manually_adjusted)
                                            <span class="mr-1 inline-flex rounded-lg bg-amber-50 dark:bg-amber-950/60 px-2 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60">Dikoreksi Admin</span>
                                        @endif
                                        <button type="button" onclick="showAttendanceDetail({{ $rec->id }})" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-[11px] rounded-xl transition-all cursor-pointer shadow-2xs inline-flex items-center gap-1">
                                            <span>Lihat Detail</span>
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500 italic">Belum Ada Data</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-3">
                @foreach($attendanceItems as $item)
                    @php
                        $emp = $item['employee'];
                        $sched = $item['schedule'];
                        $rec = $item['record'];
                        $effective = $item['effective_schedule'] ?? null;
                    @endphp
                    <div class="p-4 bg-slate-50/70 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 rounded-2xl space-y-3 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60 font-bold flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                    {{ substr($emp->full_name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-slate-100">{{ $emp->full_name }}</h4>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?? 'Staf' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $item['badge_class'] }}">
                                {{ $item['status_label'] }}
                            </span>
                        </div>

                        @if($effective && in_array($effective['source'], ['public_holiday', 'company_holiday'], true))
                            <div class="rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 px-3 py-2 text-[10px] font-black text-amber-900 dark:text-amber-300">LIBUR · {{ $effective['holiday_name'] }}</div>
                        @elseif($effective && $effective['source'] === 'employee_override')
                            <div class="rounded-xl border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50 dark:bg-indigo-950/60 px-3 py-2 text-[10px] font-black text-indigo-900 dark:text-indigo-300">{{ $effective['is_working_day'] ? 'JADWAL KHUSUS · '.($effective['shift']?->code ?? '') : 'LIBUR KHUSUS' }}</div>
                        @elseif($effective && $effective['source'] === 'special_working_day')
                            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 px-3 py-2 text-[10px] font-black text-emerald-900 dark:text-emerald-300">HARI KERJA KHUSUS</div>
                        @endif

                        <div class="grid grid-cols-2 gap-2 text-[11px] border-t border-b border-slate-200/80 dark:border-slate-700/80 py-2.5 font-mono">
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-normal block font-sans">Masuk</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100">{{ $rec?->check_in_at ? $rec->check_in_at->format('H:i') : '--:--' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-normal block font-sans">Keluar</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100">{{ $rec?->check_out_at ? $rec->check_out_at->format('H:i') : '--:--' }}</span>
                                @if($rec && $rec->checkout_source === 'auto_shift_end')
                                    <span class="block text-[9px] font-extrabold text-indigo-600 dark:text-indigo-400 font-sans">Auto</span>
                                @endif
                            </div>
                        </div>

                        @if($rec)
                            @if($rec->is_manually_adjusted)
                                <div class="rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200/80 dark:border-amber-800/60 px-2.5 py-1.5 text-[10px] font-bold text-amber-700 dark:text-amber-300">Dikoreksi Admin · {{ $rec->corrected_at?->format('d/m/Y H:i') }}</div>
                            @endif
                            <div class="flex items-center justify-between pt-1">
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium space-y-0.5">
                                    @if($rec->check_in_distance_meters !== null)
                                        <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $rec->getWorkOutletName() }}</span>
                                        @if(!empty($item['is_temporary_assignment']))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 my-0.5">PENUGASAN OUTLET</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium block">Home: {{ $item['historical_home_outlet']?->name ?? '-' }}</span>
                                        @endif
                                        <span class="block">📍 Jarak: {{ round($rec->check_in_distance_meters) }}m (±{{ round($rec->check_in_accuracy_meters) }}m)</span>
                                    @endif
                                </div>
                                <button type="button" onclick="showAttendanceDetail({{ $rec->id }})" class="min-h-[44px] px-3.5 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-extrabold text-[11px] rounded-xl transition-colors cursor-pointer shadow-xs">
                                    Lihat Detail
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<!-- Attendance Evidence Detail Modal Container -->
<div id="attendance-detail-modal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative border border-slate-200/80 dark:border-slate-800 max-h-[calc(100dvh-2rem)] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Detail Bukti Presensi</h3>
                <p id="modal-employee-name" class="text-xs text-slate-500 dark:text-slate-400 font-semibold mt-0.5">Memuat data...</p>
            </div>
            <button type="button" onclick="closeAttendanceModal()" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer min-h-[44px] min-w-[44px] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="modal-body-content" class="space-y-4 text-xs">
            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl text-center text-slate-500 dark:text-slate-400 font-medium">
                Memuat data detail presensi...
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function showAttendanceDetail(recordId) {
    const modal = document.getElementById('attendance-detail-modal');
    const modalBody = document.getElementById('modal-body-content');
    const modalEmp = document.getElementById('modal-employee-name');

    modal.classList.remove('hidden');
    modalBody.innerHTML = '<div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-center text-slate-500 dark:text-slate-400 font-medium">Memuat data detail bukti...</div>';

    try {
        const response = await fetch('/admin/attendance/' + recordId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json();

        if (!json.success || !json.data) {
            modalBody.innerHTML = '<div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-xl text-center font-bold">Gagal memuat detail data.</div>';
            return;
        }

        const data = json.data;
        const emp = data.employee || {};
        const sched = data.schedule || {};
        const shift = sched.shift || {};
        const workOutlet = json.work_outlet || data.outlet || null;
        const legacyLoc = data.location || null;

        // Determine WORK Outlet display name:
        // 1. work_outlet or data.outlet (primary snapshot from attendance_records.outlet_id)
        // 2. If outlet_id exists but outlet data missing: 'Outlet Tidak Ditemukan'
        // 3. Fallback to legacy location ONLY if record has no outlet_id
        // 4. Default: 'Lokasi Belum Dikonfigurasi'
        let locationName;
        if (json.work_outlet_name) {
            locationName = json.work_outlet_name;
        } else if (workOutlet && workOutlet.name) {
            locationName = workOutlet.name;
        } else if (data.outlet_id) {
            locationName = 'Outlet Tidak Ditemukan';
        } else if (legacyLoc && legacyLoc.name) {
            locationName = legacyLoc.name;
        } else {
            locationName = 'Lokasi Belum Dikonfigurasi';
        }

        const isTemporaryAssignment = Boolean(json.is_temporary_assignment);
        const homeOutletName = json.historical_home_outlet?.name || '-';

        const checkInDistance = data.check_in_distance_meters !== null && data.check_in_distance_meters !== undefined
            ? Math.round(data.check_in_distance_meters) + 'm'
            : '--';
        const checkInAccuracy = data.check_in_accuracy_meters !== null && data.check_in_accuracy_meters !== undefined
            ? '±' + Math.round(data.check_in_accuracy_meters) + 'm'
            : '--';

        const checkOutDistance = data.check_out_distance_meters !== null && data.check_out_distance_meters !== undefined
            ? Math.round(data.check_out_distance_meters) + 'm'
            : '--';
        const checkOutAccuracy = data.check_out_accuracy_meters !== null && data.check_out_accuracy_meters !== undefined
            ? '±' + Math.round(data.check_out_accuracy_meters) + 'm'
            : '--';

        modalEmp.innerText = (emp.full_name || 'Karyawan') + ' (' + (emp.employee_code || '') + ') — ' + (data.work_date || '');

        let html = `
            <div class="grid grid-cols-2 gap-3 bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Shift Kerja</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 block mt-0.5">${escapeHtml(shift.name || '--')} (${escapeHtml(shift.code || '--')})</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-mono mt-0.5">${shift.start_time ? escapeHtml(shift.start_time.substring(0,5)) : ''} - ${shift.end_time ? escapeHtml(shift.end_time.substring(0,5)) : ''} WIB</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Lokasi Toko</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 block mt-0.5">${escapeHtml(locationName)}</span>
                    ${isTemporaryAssignment ? `
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 my-0.5">PENUGASAN OUTLET</span>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium block">Home: ${escapeHtml(homeOutletName)}</span>
                    ` : ''}
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block mt-0.5">📍 Jarak: ${escapeHtml(checkInDistance)} (Akurasi ${escapeHtml(checkInAccuracy)})</span>
                </div>
            </div>

            <!-- Check-in Evidence Card -->
            <div class="p-4 bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 rounded-2xl space-y-2.5">
                <div class="flex items-center justify-between border-b border-emerald-200/60 dark:border-emerald-800/40 pb-2">
                    <span class="font-extrabold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Bukti Check-In (Masuk)</span>
                    </span>
                    <span class="font-mono font-bold text-emerald-800 dark:text-emerald-300">${data.check_in_at ? new Date(data.check_in_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) + ' WIB' : '--:--'}</span>
                </div>

                <div class="flex items-center gap-3.5">
                    ${json.check_in_selfie_url ? `<a href="${encodeURI(json.check_in_selfie_url)}" target="_blank"><img src="${encodeURI(json.check_in_selfie_url)}" alt="Selfie Masuk" class="w-20 h-20 rounded-xl object-cover border border-emerald-300 dark:border-emerald-700 shadow-2xs hover:opacity-90 transition-opacity"></a>` : '<div class="w-20 h-20 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-[10px] text-emerald-700 dark:text-emerald-300 font-bold">No Selfie</div>'}
                    <div class="space-y-1 text-[11px] text-emerald-800 dark:text-emerald-300">
                        <p><strong>GPS:</strong> ${escapeHtml(data.check_in_latitude ?? '--')}, ${escapeHtml(data.check_in_longitude ?? '--')}</p>
                        <p><strong>Akurasi GPS:</strong> ${escapeHtml(checkInAccuracy)}</p>
                        <p><strong>Jarak:</strong> ${escapeHtml(checkInDistance)}</p>
                        <p><strong>IP & Browser:</strong> ${escapeHtml(data.check_in_ip || '--')}</p>
                    </div>
                </div>
            </div>

            <!-- Check-out Evidence Card -->
            <div class="p-4 bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 rounded-2xl space-y-2.5">
                <div class="flex items-center justify-between border-b border-sky-200/60 dark:border-sky-800/40 pb-2">
                    <span class="font-extrabold text-sky-900 dark:text-sky-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Bukti Check-Out (Pulang)</span>
                    </span>
                    <span class="font-mono font-bold text-sky-800 dark:text-sky-300">
                        ${data.check_out_at ? new Date(data.check_out_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) + ' WIB' : '--:--'}
                        ${data.checkout_source === 'auto_shift_end' ? '<span class="ml-1.5 px-2 py-0.5 rounded-md text-[9px] font-extrabold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Auto Checkout</span>' : ''}
                    </span>
                </div>

                <div class="flex items-center gap-3.5">
                    ${json.check_out_selfie_url ? `<a href="${encodeURI(json.check_out_selfie_url)}" target="_blank"><img src="${encodeURI(json.check_out_selfie_url)}" alt="Selfie Pulang" class="w-20 h-20 rounded-xl object-cover border border-sky-300 dark:border-sky-700 shadow-2xs hover:opacity-90 transition-opacity"></a>` : '<div class="w-20 h-20 rounded-xl bg-sky-100 dark:bg-sky-900/60 border border-sky-200 dark:border-sky-800 flex items-center justify-center text-[10px] text-sky-700 dark:text-sky-300 font-bold">No Selfie</div>'}
                    <div class="space-y-1 text-[11px] text-sky-800 dark:text-sky-300">
                        <p><strong>GPS:</strong> ${escapeHtml(data.check_out_latitude ?? '--')}, ${escapeHtml(data.check_out_longitude ?? '--')}</p>
                        <p><strong>Akurasi GPS:</strong> ${escapeHtml(checkOutAccuracy)}</p>
                        <p><strong>Jarak:</strong> ${escapeHtml(checkOutDistance)}</p>
                        <p><strong>IP & Browser:</strong> ${escapeHtml(data.check_out_ip || '--')}</p>
                    </div>
                </div>
            </div>

            <!-- Calculated Metrics Summary -->
            <div class="grid grid-cols-4 gap-2 pt-1 font-mono text-[11px]">
                <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-center">
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-sans font-bold uppercase tracking-wider block">Terlambat</span>
                    <span class="font-extrabold text-slate-900 dark:text-slate-100">${Number(data.late_minutes) || 0}m</span>
                </div>
                <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-center">
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-sans font-bold uppercase tracking-wider block">Worked Time</span>
                    <span class="font-extrabold text-slate-900 dark:text-slate-100">${Math.floor((Number(data.worked_minutes) || 0)/60)}j ${(Number(data.worked_minutes) || 0)%60}m</span>
                </div>
                <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-center">
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-sans font-bold uppercase tracking-wider block">Pulang Awal</span>
                    <span class="font-extrabold text-slate-900 dark:text-slate-100">${Number(data.early_leave_minutes) || 0}m</span>
                </div>
                <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-center">
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-sans font-bold uppercase tracking-wider block">Lembur Harian</span>
                    <span class="font-extrabold text-slate-900 dark:text-slate-100">${Number(data.overtime_minutes) || 0}m</span>
                </div>
            </div>

            <details class="rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/60 dark:bg-amber-950/40 p-4 transition-colors">
                <summary class="flex min-h-[44px] cursor-pointer items-center font-extrabold text-amber-900 dark:text-amber-300">Koreksi Absensi</summary>
                <form method="POST" action="${escapeHtml(json.correction_url || '')}" class="mt-3 space-y-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block min-w-0"><span class="mb-1 block text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Jam Masuk</span><input type="datetime-local" name="check_in_at" value="${escapeHtml(json.check_in_local || '')}" class="min-h-[44px] w-full min-w-0 max-w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></label>
                        <label class="block min-w-0"><span class="mb-1 block text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Jam Pulang</span><input type="datetime-local" name="check_out_at" value="${escapeHtml(json.check_out_local || '')}" class="min-h-[44px] w-full min-w-0 max-w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></label>
                    </div>
                    <label class="block"><span class="mb-1 block text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Alasan Koreksi *</span><textarea name="reason" minlength="5" maxlength="2000" required rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 p-3 text-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" placeholder="Contoh: Lupa absen pulang"></textarea></label>
                    <label class="block"><span class="mb-1 block text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Catatan Internal (opsional)</span><textarea name="internal_note" maxlength="2000" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 p-3 text-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></textarea></label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Status, keterlambatan, dan menit kerja dihitung ulang otomatis. Bukti GPS/selfie asli tidak diubah.</p>
                    <button type="submit" class="min-h-[44px] w-full rounded-xl bg-amber-600 hover:bg-amber-700 px-4 py-2.5 text-xs font-extrabold text-white transition-colors cursor-pointer shadow-xs">Simpan Koreksi</button>
                </form>
            </details>
        `;

        modalBody.innerHTML = html;
    } catch (err) {
        modalBody.innerHTML = '<div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-xl text-center font-bold">Gagal mengambil data detail dari server.</div>';
    }
}

function closeAttendanceModal() {
    document.getElementById('attendance-detail-modal').classList.add('hidden');
}
</script>
@endsection
