@extends('layouts.admin')

@section('title', 'Dashboard Owner')
@section('page-title', 'Dashboard Monitoring Kehadiran')

@section('content')
<div class="space-y-6">

    <!-- Header Greeting & Date Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring Presensi Toko Hari Ini</h2>
            <p class="text-xs text-slate-500 mt-0.5">Ringkasan kehadiran karyawan SELON BEAUTY secara real-time</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ $todayFormatted }}</span>
            </span>
            <a href="{{ route('admin.attendance.index') }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3 py-1.5 rounded-xl transition-colors">
                <span>Buka Monitoring Lengkap →</span>
            </a>
        </div>
    </div>

    <!-- Colorful Semantic KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <!-- Card 1: Total Employee -->
        <div class="bg-gradient-to-br from-blue-500/10 to-indigo-500/5 bg-white rounded-2xl p-4 border border-blue-200/80 shadow-xs relative overflow-hidden group hover:border-blue-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-blue-700 uppercase tracking-wider">Karyawan Aktif</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['total_employees'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-blue-600/90 mt-2 font-semibold">Total terdaftar</p>
        </div>

        <!-- Card 2: Hadir Hari Ini -->
        <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs relative overflow-hidden group hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Hadir Hari Ini</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['present_today'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-emerald-600/90 mt-2 font-semibold">Sudah check-in</p>
        </div>

        <!-- Card 3: Terlambat -->
        <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 bg-white rounded-2xl p-4 border border-amber-200/80 shadow-xs relative overflow-hidden group hover:border-amber-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-amber-700 uppercase tracking-wider">Terlambat</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['late_today'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-amber-600/90 mt-2 font-semibold">Lewat toleransi</p>
        </div>

        <!-- Card 4: Belum Check-in -->
        <div class="bg-gradient-to-br from-purple-500/10 to-indigo-500/5 bg-white rounded-2xl p-4 border border-purple-200/80 shadow-xs relative overflow-hidden group hover:border-purple-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-purple-700 uppercase tracking-wider">Belum Check-in</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['pending_check_in_today'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white shadow-md shadow-purple-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-purple-600/90 mt-2 font-semibold">Dalam window masukan</p>
        </div>

        <!-- Card 5: Tidak Hadir -->
        <div class="bg-gradient-to-br from-rose-500/10 to-red-500/5 bg-white rounded-2xl p-4 border border-rose-200/80 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-rose-700 uppercase tracking-wider">Tidak Hadir</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['absent_today'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white shadow-md shadow-rose-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-rose-600/90 mt-2 font-semibold">Batas check-in lewat</p>
        </div>

        <!-- Card 6: Izin / Sakit / Cuti -->
        <div class="bg-gradient-to-br from-indigo-500/10 to-sky-500/5 bg-white rounded-2xl p-4 border border-indigo-200/80 shadow-xs relative overflow-hidden group hover:border-indigo-300 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-extrabold text-indigo-700 uppercase tracking-wider">Izin / Cuti</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $metrics['leave_today'] }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-indigo-600/90 mt-2 font-semibold">Izin, Sakit, atau Cuti</p>
        </div>
    </div>

    <!-- Attendance Today Table & Mobile Cards -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 md:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Kehadiran Hari Ini</h3>
                <p class="text-xs text-slate-500">Daftar real-time seluruh karyawan dan status presensi jadwal hari ini</p>
            </div>
        </div>

        @if($metrics['total_employees'] == 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Belum Ada Karyawan Terdaftar</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Belum ada karyawan aktif terdaftar di sistem toko SELON BEAUTY.</p>
            </div>
        @elseif(count($attendanceItems) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Tidak Ada Data Kehadiran</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Belum ada karyawan yang memiliki jadwal kerja atau catatan presensi untuk hari ini.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/80">
                            <th class="py-3 px-4 rounded-l-xl">Karyawan</th>
                            <th class="py-3 px-4">Shift</th>
                            <th class="py-3 px-4">Jam Masuk</th>
                            <th class="py-3 px-4">Jam Pulang</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Terlambat</th>
                            <th class="py-3 px-4">Worked Time</th>
                            <th class="py-3 px-4 text-right rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($attendanceItems as $item)
                            @php
                                $emp = $item['employee'];
                                $sched = $item['schedule'];
                                $rec = $item['record'];
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ substr($emp->full_name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-900">{{ $emp->full_name }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?? 'Staf Toko' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-medium">
                                    @if($sched && $sched->shift)
                                        <span class="font-bold text-slate-800 block">{{ $sched->shift->name }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ substr($sched->shift->start_time, 0, 5) }} - {{ substr($sched->shift->end_time, 0, 5) }}</span>
                                    @elseif($sched && $sched->schedule_type === 'off')
                                        <span class="text-slate-500 font-semibold italic">OFF Pekanan</span>
                                    @elseif($sched && $sched->schedule_type === 'holiday')
                                        <span class="text-amber-700 font-semibold italic">Libur Toko</span>
                                    @else
                                        <span class="text-slate-400 italic">Belum Ada Shift</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ $rec?->check_in_at ? $rec->check_in_at->format('H:i') : '--:--' }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ $rec?->check_out_at ? $rec->check_out_at->format('H:i') : '--:--' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $item['badge_class'] }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    @if($rec && $rec->late_minutes > 0)
                                        <span class="text-rose-600 font-bold">{{ $rec->late_minutes }}m</span>
                                    @else
                                        <span class="text-slate-400">0m</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    @if($rec && $rec->worked_minutes > 0)
                                        <span class="font-bold text-slate-800">{{ floor($rec->worked_minutes / 60) }}j {{ $rec->worked_minutes % 60 }}m</span>
                                    @else
                                        <span class="text-slate-400">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($rec)
                                        <button type="button" onclick="showAttendanceDetail({{ $rec->id }})" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg transition-colors cursor-pointer">
                                            Lihat Detail
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Belum Ada Presensi</span>
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
                    @endphp
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-xs shrink-0">
                                    {{ substr($emp->full_name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900">{{ $emp->full_name }}</h4>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?? 'Staf' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $item['badge_class'] }}">
                                {{ $item['status_label'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] border-t border-b border-slate-200/60 py-2 font-mono">
                            <div>
                                <span class="text-[10px] text-slate-400 font-normal block font-sans">Masuk</span>
                                <span class="font-extrabold text-slate-900">{{ $rec?->check_in_at ? $rec->check_in_at->format('H:i') : '--:--' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-normal block font-sans">Keluar</span>
                                <span class="font-extrabold text-slate-900">{{ $rec?->check_out_at ? $rec->check_out_at->format('H:i') : '--:--' }}</span>
                            </div>
                        </div>

                        @if($rec)
                            <div class="flex items-center justify-between pt-1">
                                <div class="text-[10px] text-slate-500 font-medium">
                                    @if($rec->late_minutes > 0)
                                        <span class="text-rose-600 font-bold">Terlambat {{ $rec->late_minutes }}m</span>
                                    @else
                                        <span class="text-emerald-600 font-bold">Tepat Waktu</span>
                                    @endif
                                </div>
                                <button type="button" onclick="showAttendanceDetail({{ $rec->id }})" class="px-3 py-1 bg-slate-900 text-white font-extrabold text-[11px] rounded-lg transition-colors cursor-pointer">
                                    Lihat Detail
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Past 7-Day Trend Section / Empty State Card (Section 10) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 md:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tren Kehadiran 7 Hari Terakhir</h3>
                <p class="text-xs text-slate-500">Statistik kehadiran dari database nyata</p>
            </div>
        </div>

        @if(!$trendData['has_data'])
            <!-- Clean Empty State if insufficient data -->
            <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                <h4 class="text-xs font-extrabold text-slate-700">Belum Cukup Data Kehadiran</h4>
                <p class="text-[11px] text-slate-500 max-w-sm mx-auto mt-1">Belum cukup data kehadiran untuk menampilkan tren 7 hari terakhir.</p>
            </div>
        @else
            <!-- Real Trend Bars -->
            <div class="grid grid-cols-7 gap-2 pt-2">
                @foreach($trendData['data'] as $t)
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-700 font-mono">{{ $t['total'] }}</span>
                        <div class="w-full bg-slate-100 rounded-lg h-24 flex flex-col justify-end p-1 overflow-hidden">
                            @if($t['total'] > 0)
                                <div class="w-full bg-emerald-500 rounded-md" style="height: {{ min(100, max(15, $t['present'] * 20)) }}%"></div>
                            @endif
                        </div>
                        <span class="text-[10px] font-semibold text-slate-500">{{ $t['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<!-- Attendance Evidence Detail Modal Container -->
<div id="attendance-detail-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Detail Bukti Presensi</h3>
                <p id="modal-employee-name" class="text-xs text-slate-500 font-semibold mt-0.5">Memuat data...</p>
            </div>
            <button type="button" onclick="closeAttendanceModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="modal-body-content" class="space-y-4 text-xs">
            <div class="p-4 bg-slate-50 rounded-xl text-center text-slate-500">
                Memuat data detail presensi...
            </div>
        </div>
    </div>
</div>

<script>
async function showAttendanceDetail(recordId) {
    const modal = document.getElementById('attendance-detail-modal');
    const modalBody = document.getElementById('modal-body-content');
    const modalEmp = document.getElementById('modal-employee-name');

    modal.classList.remove('hidden');
    modalBody.innerHTML = '<div class="p-4 bg-slate-50 rounded-xl text-center text-slate-500 font-medium">Memuat data detail bukti...</div>';

    try {
        const response = await fetch('/admin/attendance/' + recordId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json();

        if (!json.success || !json.data) {
            modalBody.innerHTML = '<div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-center font-bold">Gagal memuat detail data.</div>';
            return;
        }

        const data = json.data;
        const emp = data.employee || {};
        const sched = data.schedule || {};
        const shift = sched.shift || {};
        const loc = data.location || {};

        modalEmp.innerText = (emp.full_name || 'Karyawan') + ' (' + (emp.employee_code || '') + ') — ' + (data.work_date || '');

        let html = `
            <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block">Shift Kerja</span>
                    <span class="font-bold text-slate-900">${shift.name || '--'} (${shift.code || '--'})</span>
                    <span class="text-[10px] text-slate-500 block font-mono">${shift.start_time ? shift.start_time.substring(0,5) : ''} - ${shift.end_time ? shift.end_time.substring(0,5) : ''} WIB</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block">Lokasi Toko</span>
                    <span class="font-bold text-slate-900">${loc.name || 'SELON BEAUTY'}</span>
                    <span class="text-[10px] text-emerald-600 font-bold block">📍 Jarak: ${data.check_in_distance_meters !== null ? Math.round(data.check_in_distance_meters) + 'm' : '--'} (Akurasi ±${data.check_in_accuracy_meters !== null ? Math.round(data.check_in_accuracy_meters) + 'm' : '--'})</span>
                </div>
            </div>

            <!-- Check-in Evidence Card -->
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-2">
                <div class="flex items-center justify-between border-b border-emerald-200/60 pb-2">
                    <span class="font-extrabold text-emerald-900 flex items-center gap-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Bukti Check-In (Masuk)</span>
                    </span>
                    <span class="font-mono font-bold text-emerald-800">${data.check_in_at ? new Date(data.check_in_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) + ' WIB' : '--:--'}</span>
                </div>

                <div class="flex items-center gap-3">
                    ${json.check_in_selfie_url ? `<a href="${json.check_in_selfie_url}" target="_blank"><img src="${json.check_in_selfie_url}" alt="Selfie Masuk" class="w-20 h-20 rounded-lg object-cover border border-emerald-300 shadow-2xs hover:opacity-90 transition-opacity"></a>` : '<div class="w-20 h-20 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-[10px] text-emerald-700 font-bold">No Selfie</div>'}
                    <div class="space-y-1 text-[11px] text-emerald-800">
                        <p><strong>GPS:</strong> ${data.check_in_latitude || '--'}, ${data.check_in_longitude || '--'}</p>
                        <p><strong>Akurasi GPS:</strong> ±${data.check_in_accuracy_meters !== null ? Math.round(data.check_in_accuracy_meters) : '--'}m</p>
                        <p><strong>Jarak:</strong> ${data.check_in_distance_meters !== null ? Math.round(data.check_in_distance_meters) : '--'}m</p>
                        <p><strong>IP & Browser:</strong> ${data.check_in_ip || '--'}</p>
                    </div>
                </div>
            </div>

            <!-- Check-out Evidence Card -->
            <div class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2">
                    <span class="font-extrabold text-indigo-900 flex items-center gap-1">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Bukti Check-Out (Pulang)</span>
                    </span>
                    <span class="font-mono font-bold text-indigo-800">${data.check_out_at ? new Date(data.check_out_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) + ' WIB' : '--:--'}</span>
                </div>

                <div class="flex items-center gap-3">
                    ${json.check_out_selfie_url ? `<a href="${json.check_out_selfie_url}" target="_blank"><img src="${json.check_out_selfie_url}" alt="Selfie Pulang" class="w-20 h-20 rounded-lg object-cover border border-indigo-300 shadow-2xs hover:opacity-90 transition-opacity"></a>` : '<div class="w-20 h-20 rounded-lg bg-indigo-100 border border-indigo-200 flex items-center justify-center text-[10px] text-indigo-700 font-bold">No Selfie</div>'}
                    <div class="space-y-1 text-[11px] text-indigo-800">
                        <p><strong>GPS:</strong> ${data.check_out_latitude || '--'}, ${data.check_out_longitude || '--'}</p>
                        <p><strong>Akurasi GPS:</strong> ±${data.check_out_accuracy_meters !== null ? Math.round(data.check_out_accuracy_meters) : '--'}m</p>
                        <p><strong>Jarak:</strong> ${data.check_out_distance_meters !== null ? Math.round(data.check_out_distance_meters) : '--'}m</p>
                        <p><strong>IP & Browser:</strong> ${data.check_out_ip || '--'}</p>
                    </div>
                </div>
            </div>

            <!-- Calculated Metrics Summary -->
            <div class="grid grid-cols-4 gap-2 pt-1 font-mono text-[11px]">
                <div class="bg-slate-100 p-2 rounded-lg text-center">
                    <span class="text-[9px] text-slate-500 font-sans block">Terlambat</span>
                    <span class="font-extrabold text-slate-900">${data.late_minutes || 0}m</span>
                </div>
                <div class="bg-slate-100 p-2 rounded-lg text-center">
                    <span class="text-[9px] text-slate-500 font-sans block">Worked Time</span>
                    <span class="font-extrabold text-slate-900">${Math.floor((data.worked_minutes || 0)/60)}j ${(data.worked_minutes || 0)%60}m</span>
                </div>
                <div class="bg-slate-100 p-2 rounded-lg text-center">
                    <span class="text-[9px] text-slate-500 font-sans block">Pulang Awal</span>
                    <span class="font-extrabold text-slate-900">${data.early_leave_minutes || 0}m</span>
                </div>
                <div class="bg-slate-100 p-2 rounded-lg text-center">
                    <span class="text-[9px] text-slate-500 font-sans block">Lembur Harian</span>
                    <span class="font-extrabold text-slate-900">${data.overtime_minutes || 0}m</span>
                </div>
            </div>
        `;

        modalBody.innerHTML = html;
    } catch (err) {
        modalBody.innerHTML = '<div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-center font-bold">Gagal mengambil data detail dari server.</div>';
    }
}

function closeAttendanceModal() {
    document.getElementById('attendance-detail-modal').classList.add('hidden');
}
</script>
@endsection
