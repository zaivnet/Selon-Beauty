@extends('layouts.admin')

@section('title', 'Kelola Pengajuan Lembur')
@section('page-title', 'Persetujuan & Riwayat Lembur')

@section('content')
<div class="space-y-6">

    <!-- Flash Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Terjadi Kesalahan Process:</p>
            <ul class="list-disc list-inside space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex bg-slate-200/80 p-1 rounded-xl gap-1 text-xs font-bold">
        <a href="{{ route('admin.leave-requests.index') }}" class="flex-1 text-center py-2.5 px-3 rounded-xl transition-all min-h-[44px] flex items-center justify-center {{ request()->routeIs('admin.leave-requests.*') ? 'bg-white text-rose-700 shadow-sm font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold' }}">
            Izin & Cuti
        </a>
        <a href="{{ route('admin.overtime-requests.index') }}" class="flex-1 text-center py-2.5 px-3 rounded-xl transition-all min-h-[44px] flex items-center justify-center {{ request()->routeIs('admin.overtime-requests.*') ? 'bg-white text-rose-700 shadow-sm font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold' }}">
            Pengajuan Lembur
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white p-5 md:p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Kelola Pengajuan Lembur</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">Review dan tentukan menit lembur yang disetujui (Approved Overtime Minutes) karyawan.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.overtime-requests.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                    <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                    <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                    <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Dibatalkan (Cancelled)</option>
                </select>
            </div>

            <!-- Employee Filter -->
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

            <!-- Start Date Filter -->
            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full min-w-0 max-w-full box-border px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
            </div>

            <!-- End Date Filter -->
            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full min-w-0 max-w-full box-border px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.overtime-requests.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center cursor-pointer min-h-[44px]">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900">Daftar Pengajuan Lembur</h3>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ count($requests) }} Pengajuan</span>
        </div>

        @if(count($requests) === 0)
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-bold text-slate-700">Tidak ada pengajuan lembur yang ditemukan.</p>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan filter atau rentang tanggal pencarian Anda.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Karyawan</th>
                            <th class="px-5 py-3.5">Tanggal Kerja</th>
                            <th class="px-5 py-3.5">Jadwal Shift</th>
                            <th class="px-5 py-3.5">Absensi Aktual</th>
                            <th class="px-5 py-3.5">Candidate Lembur</th>
                            <th class="px-5 py-3.5">Requested</th>
                            <th class="px-5 py-3.5">Approved</th>
                            <th class="px-5 py-3.5">Alasan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($requests as $req)
                            @php
                                $dateKey = $req->employee_id . '_' . $req->work_date->format('Y-m-d');
                                $att = $attendances[$dateKey] ?? null;
                                $sch = $schedules[$dateKey] ?? null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Karyawan -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ $req->employee->full_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">{{ $req->employee->employee_code }} • {{ $req->employee->jobTitle?->name ?? 'Karyawan' }}</div>
                                </td>

                                <!-- Tanggal Kerja -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $req->work_date->translatedFormat('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $req->work_date->translatedFormat('l') }}</div>
                                </td>

                                <!-- Shift -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($sch && $sch->shift)
                                        <div class="font-bold text-slate-800">{{ $sch->shift->name }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium">{{ substr($sch->shift->start_time, 0, 5) }} - {{ substr($sch->shift->end_time, 0, 5) }}</div>
                                    @else
                                        <span class="text-slate-400 font-medium">-</span>
                                    @endif
                                </td>

                                <!-- Absensi Aktual -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($att)
                                        <div class="font-semibold text-slate-800">In: {{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }} | Out: {{ $att->check_out_at ? $att->check_out_at->format('H:i') : '-' }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium">Worked: {{ (int) floor(($att->worked_minutes ?? 0) / 60) }}j {{ ($att->worked_minutes ?? 0) % 60 }}m</div>
                                    @else
                                        <span class="text-slate-400 font-medium italic">Belum Ada Record</span>
                                    @endif
                                </td>

                                <!-- Candidate Lembur (from Attendance) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($att)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-pink-50 text-pink-700 border border-pink-200">
                                            {{ $att->overtime_minutes }} menit
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-medium">0m</span>
                                    @endif
                                </td>

                                <!-- Requested Minutes -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-black text-slate-900">{{ $req->formatted_requested_duration }}</span>
                                </td>

                                <!-- Approved Minutes -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($req->status === 'approved')
                                        <span class="font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                            {{ $req->formatted_approved_duration }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-medium">-</span>
                                    @endif
                                </td>

                                <!-- Alasan -->
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="text-slate-700 font-medium line-clamp-2">{{ $req->reason }}</p>
                                    @if($req->reviewer_note)
                                        <p class="text-[10px] text-slate-500 bg-slate-100 p-1.5 rounded mt-1">
                                            <strong>Catatan:</strong> {{ $req->reviewer_note }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Approve Modal Trigger -->
                                            <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ $req->employee->full_name }}', {{ $req->requested_minutes }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-extrabold shadow-sm transition-all cursor-pointer">
                                                Setujui
                                            </button>

                                            <!-- Reject Modal Trigger -->
                                            <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ $req->employee->full_name }}')" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-[11px] font-extrabold transition-all cursor-pointer">
                                                Tolak
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-400 font-medium">
                                            @if($req->reviewer)
                                                Oleh: {{ $req->reviewer->name }}<br>
                                                {{ $req->reviewed_at ? $req->reviewed_at->format('d/m H:i') : '' }}
                                            @else
                                                Selesai
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Approve Overtime -->
<div id="approveModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-extrabold text-slate-900">Setujui Pengajuan Lembur</h3>
            <button type="button" onclick="closeApproveModal()" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
        </div>

        <form id="approveForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-600 font-medium">Karyawan: <strong id="approve_emp_name" class="text-slate-900"></strong></p>
                <p class="text-slate-600 font-medium mt-0.5">Durasi Diajukan: <strong id="approve_req_mins" class="text-rose-600"></strong></p>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Durasi Disetujui (Menit) <span class="text-rose-600">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="approved_minutes" id="approved_minutes" min="0" required class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-extrabold text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <span class="text-xs font-bold text-slate-500">Menit</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-1">Anda dapat menyetujui menit yang sama atau lebih sedikit dari yang diajukan.</p>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Reviewer (Opsional)</label>
                <textarea name="reviewer_note" rows="2" placeholder="Catatan persetujuan..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeApproveModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md shadow-emerald-600/20">Setujui Lembur</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject Overtime -->
<div id="rejectModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-extrabold text-slate-900">Tolak Pengajuan Lembur</h3>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
        </div>

        <form id="rejectForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-600 font-medium">Karyawan: <strong id="reject_emp_name" class="text-slate-900"></strong></p>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Alasan Penolakan <span class="text-rose-600">*</span></label>
                <textarea name="reviewer_note" rows="3" required placeholder="Jelaskan alasan penolakan pengajuan lembur ini..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl shadow-md shadow-rose-600/20">Tolak Lembur</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openApproveModal(id, empName, reqMins) {
        document.getElementById('approve_emp_name').innerText = empName;
        document.getElementById('approve_req_mins').innerText = `${reqMins} menit`;
        document.getElementById('approved_minutes').value = reqMins;
        document.getElementById('approved_minutes').max = reqMins;
        document.getElementById('approveForm').action = `/admin/overtime-requests/${id}/approve`;
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function openRejectModal(id, empName) {
        document.getElementById('reject_emp_name').innerText = empName;
        document.getElementById('rejectForm').action = `/admin/overtime-requests/${id}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endsection
