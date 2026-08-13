@extends('layouts.admin')

@section('title', 'Kelola Pengajuan Izin, Sakit & Cuti')
@section('page-title', 'Persetujuan Pengajuan Karyawan')

@section('content')
<div class="space-y-6">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Terjadi Kesalahan:</p>
            <ul class="list-disc list-inside space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Header & Filter Bar Card -->
    <div class="bg-white p-5 md:p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Kelola Pengajuan Karyawan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar pengajuan izin, sakit, dan cuti karyawan SELON BEAUTY</p>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.leave-requests.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                    <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Jenis</label>
                <select name="type" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="all">Semua Jenis</option>
                    <option value="permission" {{ $filters['type'] === 'permission' ? 'selected' : '' }}>Izin</option>
                    <option value="sick" {{ $filters['type'] === 'sick' ? 'selected' : '' }}>Sakit</option>
                    <option value="leave" {{ $filters['type'] === 'leave' ? 'selected' : '' }}>Cuti</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Karyawan</label>
                <select name="employee_id" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full min-w-0 max-w-full box-border px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1 cursor-pointer min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.leave-requests.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-colors cursor-pointer text-center min-h-[44px] flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Main Leave Requests List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 md:p-6 space-y-4">
        @if(count($requests) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Tidak Ada Pengajuan Ditemukan</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Tidak ada data pengajuan yang sesuai dengan kriteria filter saat ini.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/80">
                            <th class="py-3 px-4 rounded-l-xl">Karyawan</th>
                            <th class="py-3 px-4">Jenis</th>
                            <th class="py-3 px-4">Tanggal Range</th>
                            <th class="py-3 px-4">Alasan</th>
                            <th class="py-3 px-4">Lampiran</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($requests as $req)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ substr($req->employee->full_name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-900">{{ $req->employee->full_name }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $req->employee->employee_code }} • {{ $req->employee->jobTitle?->name ?? 'Staf' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    {{ $req->type_label }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ $req->start_date->format('d/m/Y') }}
                                    @if($req->start_date != $req->end_date)
                                        s/d {{ $req->end_date->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 max-w-xs truncate text-slate-700" title="{{ $req->reason }}">
                                    "{{ $req->reason }}"
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($req->attachment_path)
                                        <a href="{{ route('leave-requests.attachment', $req->id) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-rose-600 hover:text-rose-700 text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Lihat File</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1">
                                    @if($req->status === 'pending')
                                        <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] transition-colors cursor-pointer shadow-2xs">
                                            Setujui
                                        </button>
                                        <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-[11px] transition-colors cursor-pointer shadow-2xs">
                                            Tolak
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-medium italic">Diproses oleh {{ $req->reviewer?->name ?? 'System' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-3">
                @foreach($requests as $req)
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">{{ $req->employee->full_name }}</h4>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $req->employee->employee_code }} • {{ $req->type_label }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        <div class="text-[11px] space-y-1 border-t border-b border-slate-200/60 py-2 font-mono">
                            <p class="text-slate-800">
                                📅 {{ $req->start_date->format('d/m/Y') }} {{ $req->start_date != $req->end_date ? 's/d '.$req->end_date->format('d/m/Y') : '' }}
                            </p>
                            <p class="text-slate-600 font-sans italic">"{{ $req->reason }}"</p>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div>
                                @if($req->attachment_path)
                                    <a href="{{ route('leave-requests.attachment', $req->id) }}" target="_blank" class="font-bold text-rose-600 hover:text-rose-700 text-[11px]">
                                        📎 Lampiran
                                    </a>
                                @endif
                            </div>

                            @if($req->status === 'pending')
                                <div class="flex items-center gap-1.5">
                                    <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="px-2.5 py-1 bg-emerald-600 text-white font-bold text-[11px] rounded-lg">
                                        Setujui
                                    </button>
                                    <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="px-2.5 py-1 bg-rose-600 text-white font-bold text-[11px] rounded-lg">
                                        Tolak
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Approve Modal -->
<div id="modal-approve" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900">Setujui Pengajuan</h3>
        <p id="approve-modal-target" class="text-xs text-slate-600"></p>

        <form id="approve-form" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Persetujuan (Opsional)</label>
                <textarea name="reviewer_note" rows="2" placeholder="Catatan untuk karyawan..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeApproveModal()" class="px-3.5 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-xs">
                    Ya, Setujui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="modal-reject" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900">Tolak Pengajuan</h3>
        <p id="reject-modal-target" class="text-xs text-slate-600"></p>

        <form id="reject-form" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Alasan Penolakan <span class="text-rose-600">*</span></label>
                <textarea name="reviewer_note" rows="3" required placeholder="Tuliskan alasan penolakan agar karyawan dapat memahami..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeRejectModal()" class="px-3.5 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl shadow-xs">
                    Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(id, empName, type) {
    document.getElementById('approve-modal-target').innerText = `Setujui pengajuan ${type} atas nama ${empName}?`;
    document.getElementById('approve-form').action = `/admin/leave-requests/${id}/approve`;
    document.getElementById('modal-approve').classList.remove('hidden');
}

function closeApproveModal() {
    document.getElementById('modal-approve').classList.add('hidden');
}

function openRejectModal(id, empName, type) {
    document.getElementById('reject-modal-target').innerText = `Tolak pengajuan ${type} atas nama ${empName}?`;
    document.getElementById('reject-form').action = `/admin/leave-requests/${id}/reject`;
    document.getElementById('modal-reject').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('modal-reject').classList.add('hidden');
}
</script>
@endsection
