@extends('layouts.admin')

@section('title', 'Monitoring Absensi Karyawan')
@section('page-title', 'Monitoring Kehadiran Real-Time')

@section('content')
<div class="space-y-6">

    <!-- Header & Filter Bar Card -->
    <div class="bg-white p-5 md:p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring Absensi Karyawan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Filter dan evaluasi rincian presensi harian, lokasi GPS, dan bukti foto selfie</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ $selectedDateFormatted }}</span>
                </span>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ $filters['date'] }}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Status Kehadiran</label>
                <select name="status" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
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
                <button type="submit" class="flex-1 py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter Data</span>
                </button>
                <a href="{{ route('admin.attendance.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-colors cursor-pointer">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Metrics for Selected Date -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Total Karyawan</span>
            <span class="text-xl font-extrabold text-slate-900">{{ $metrics['total_employees'] }}</span>
        </div>
        <div class="bg-emerald-50/80 p-3.5 rounded-xl border border-emerald-200 shadow-2xs">
            <span class="text-[10px] text-emerald-700 font-bold uppercase tracking-wider block">Hadir</span>
            <span class="text-xl font-extrabold text-emerald-900">{{ $metrics['present_today'] }}</span>
        </div>
        <div class="bg-amber-50/80 p-3.5 rounded-xl border border-amber-200 shadow-2xs">
            <span class="text-[10px] text-amber-700 font-bold uppercase tracking-wider block">Terlambat</span>
            <span class="text-xl font-extrabold text-amber-900">{{ $metrics['late_today'] }}</span>
        </div>
        <div class="bg-rose-50/80 p-3.5 rounded-xl border border-rose-200 shadow-2xs">
            <span class="text-[10px] text-rose-700 font-bold uppercase tracking-wider block">Belum Check-In</span>
            <span class="text-xl font-extrabold text-rose-900">{{ $metrics['pending_check_in_today'] }}</span>
        </div>
        <div class="bg-indigo-50/80 p-3.5 rounded-xl border border-indigo-200 shadow-2xs">
            <span class="text-[10px] text-indigo-700 font-bold uppercase tracking-wider block">Izin / Sakit / Cuti</span>
            <span class="text-xl font-extrabold text-indigo-900">{{ $metrics['leave_today'] }}</span>
        </div>
    </div>

    <!-- Main Attendance Table / Cards -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 md:p-6 space-y-4">
        @if(count($attendanceItems) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Tidak Ada Data Ditemukan</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Tidak ada data kehadiran yang sesuai dengan filter yang Anda pilih.</p>
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
                            <th class="py-3 px-4">Lokasi Evidence</th>
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
                                <td class="py-3.5 px-4">
                                    @if($rec && $rec->check_in_distance_meters !== null)
                                        <span class="font-bold text-slate-800 block text-[11px]">{{ $rec->location?->name ?? 'SELON BEAUTY' }}</span>
                                        <span class="text-[10px] text-emerald-600 font-medium block">Jarak: {{ round($rec->check_in_distance_meters) }}m • Akurasi: ±{{ round($rec->check_in_accuracy_meters) }}m</span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($rec)
                                        <button type="button" onclick="showAttendanceDetail({{ $rec->id }})" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] rounded-lg transition-colors cursor-pointer shadow-2xs">
                                            Lihat Detail
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Belum Ada Data</span>
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
                                    @if($rec->check_in_distance_meters !== null)
                                        📍 Jarak: {{ round($rec->check_in_distance_meters) }}m (±{{ round($rec->check_in_accuracy_meters) }}m)
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
