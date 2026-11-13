@extends('layouts.admin')

@section('title', 'Kelola Pengajuan Izin, Sakit & Cuti')
@section('page-title', 'Persetujuan Pengajuan Karyawan')

@section('content')
<div class="space-y-6">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Terjadi Kesalahan:</p>
            <ul class="list-disc list-inside space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Workflow Navigation Tabs -->
    <div class="flex bg-slate-200/80 dark:bg-slate-800/80 p-1 rounded-xl gap-1 text-xs font-bold">
        <a href="{{ route('admin.leave-requests.index') }}" class="flex-1 text-center py-2.5 px-3 rounded-xl transition-all min-h-[44px] flex items-center justify-center {{ request()->routeIs('admin.leave-requests.*') ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-bold' }}">
            Izin & Cuti
        </a>
        <a href="{{ route('admin.overtime-requests.index') }}" class="flex-1 text-center py-2.5 px-3 rounded-xl transition-all min-h-[44px] flex items-center justify-center {{ request()->routeIs('admin.overtime-requests.*') ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-bold' }}">
            Pengajuan Lembur
        </a>
    </div>

    <!-- Header & Filter Bar Card -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Kelola Pengajuan Karyawan</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar pengajuan izin, sakit, dan cuti karyawan dengan verifikasi persetujuan multi-outlet.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-outlet-filter />
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.leave-requests.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <div>
                <label for="filter_status" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select id="filter_status" name="status" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden min-h-[44px] ui-select">
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                    <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                </select>
            </div>

            <div>
                <label for="filter_type" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Jenis</label>
                <select id="filter_type" name="type" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden min-h-[44px] ui-select">
                    <option value="all">Semua Jenis</option>
                    <option value="permission" {{ $filters['type'] === 'permission' ? 'selected' : '' }}>Izin</option>
                    <option value="sick" {{ $filters['type'] === 'sick' ? 'selected' : '' }}>Sakit</option>
                    <option value="leave" {{ $filters['type'] === 'leave' ? 'selected' : '' }}>Cuti</option>
                </select>
            </div>

            <div>
                <label for="filter_employee_id" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Karyawan</label>
                <select id="filter_employee_id" name="employee_id" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden min-h-[44px] ui-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Tanggal Mulai</label>
                <x-date-input name="start_date" value="{{ $filters['start_date'] }}" wrapperClass="bg-slate-50 dark:bg-slate-950" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 text-white font-extrabold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.leave-requests.index') }}" class="py-2.5 px-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold rounded-xl transition-colors cursor-pointer text-center min-h-[44px] flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Main Leave Requests List -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 md:p-6 space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Daftar Pengajuan</h3>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">{{ count($requests) }} Pengajuan</span>
        </div>

        @if(count($requests) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-800/40">
                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak Ada Pengajuan Ditemukan</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">Tidak ada data pengajuan yang sesuai dengan kriteria filter saat ini.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left border-collapse ui-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 bg-slate-50/80 dark:bg-slate-800/60">
                            <th class="py-3 px-4 rounded-l-xl">Karyawan</th>
                            <th class="py-3 px-4">Jenis</th>
                            <th class="py-3 px-4">Tanggal Range</th>
                            <th class="py-3 px-4">Alasan</th>
                            <th class="py-3 px-4">Lampiran</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach($requests as $req)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold flex items-center justify-center text-xs shrink-0 border border-slate-200 dark:border-slate-700">
                                            {{ substr($req->employee->full_name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-900 dark:text-slate-100">{{ $req->employee->full_name }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $req->employee->employee_code }} • {{ $req->employee->jobTitle?->name ?? 'Staf' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $req->type_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $req->start_date->format('d/m/Y') }}</span>
                                        @if($req->start_date != $req->end_date)
                                            <span class="text-slate-400 font-normal">s/d</span>
                                            <span>{{ $req->end_date->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs truncate text-slate-700 dark:text-slate-300" title="{{ $req->reason }}">
                                    "{{ $req->reason }}"
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($req->attachment_path)
                                        <a href="{{ route('leave-requests.attachment', $req->id) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 text-[11px] px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Lihat File</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 italic">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="min-h-[36px] px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-[11px] transition-colors cursor-pointer shadow-2xs inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Setujui</span>
                                        </button>
                                        <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="min-h-[36px] px-3 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-extrabold rounded-xl text-[11px] transition-colors cursor-pointer shadow-2xs inline-flex items-center gap-1 ui-btn ui-btn-primary">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Tolak</span>
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium italic">Diproses oleh {{ $req->reviewer?->name ?? 'System' }}</span>
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
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-extrabold flex items-center justify-center text-xs shrink-0 border border-slate-200 dark:border-slate-600">
                                    {{ substr($req->employee->full_name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-slate-100">{{ $req->employee->full_name }}</h4>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $req->employee->employee_code }} • {{ $req->type_label }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        <div class="text-[11px] space-y-1.5 border-t border-b border-slate-200/60 dark:border-slate-700/60 py-2.5">
                            <p class="font-mono font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ $req->start_date->format('d/m/Y') }} {{ $req->start_date != $req->end_date ? 's/d '.$req->end_date->format('d/m/Y') : '' }}</span>
                            </p>
                            <p class="text-slate-600 dark:text-slate-400 font-sans italic">"{{ $req->reason }}"</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 gap-2">
                            <div>
                                @if($req->attachment_path)
                                    <a href="{{ route('leave-requests.attachment', $req->id) }}" target="_blank" class="font-extrabold text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 text-[11px] min-h-[44px] inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lampiran</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 text-[11px] italic">Tanpa lampiran</span>
                                @endif
                            </div>

                            @if($req->status === 'pending')
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="min-h-[44px] px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors">
                                        Setujui
                                    </button>
                                    <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', '{{ $req->type_label }}')" class="min-h-[44px] px-3.5 py-2 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 font-extrabold text-xs rounded-xl shadow-xs transition-colors ui-btn ui-btn-primary">
                                        Tolak
                                    </button>
                                </div>
                            @else
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium italic">Diproses: {{ $req->reviewer?->name ?? 'System' }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Approve Modal -->
<div id="modal-approve" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="approve-modal-title">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="approve-modal-title" class="text-base font-extrabold text-slate-900 dark:text-slate-100">Setujui Pengajuan</h3>
            <button type="button" onclick="closeApproveModal()" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
        </div>
        <p id="approve-modal-target" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"></p>

        <form id="approve-form" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <label for="approve_reviewer_note" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Persetujuan (Opsional)</label>
                <textarea id="approve_reviewer_note" name="reviewer_note" rows="3" placeholder="Catatan untuk karyawan..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeApproveModal()" class="min-h-[44px] px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="min-h-[44px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-xs cursor-pointer">
                    Ya, Setujui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="modal-reject" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="reject-modal-title">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="reject-modal-title" class="text-base font-extrabold text-slate-900 dark:text-slate-100">Tolak Pengajuan</h3>
            <button type="button" onclick="closeRejectModal()" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
        </div>
        <p id="reject-modal-target" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"></p>

        <form id="reject-form" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <label for="reject_reviewer_note" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Alasan Penolakan <span class="text-rose-600 dark:text-rose-400">*</span></label>
                <textarea id="reject_reviewer_note" name="reviewer_note" rows="3" required placeholder="Tuliskan alasan penolakan agar karyawan dapat memahami..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRejectModal()" class="min-h-[44px] px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="min-h-[44px] px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl shadow-xs ui-btn ui-btn-primary cursor-pointer">
                    Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let lastLeaveTrigger = null;

function trapModalFocus(modal, e) {
    if (e.key !== 'Tab') return;
    const focusable = modal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) {
        last.focus();
        e.preventDefault();
    } else if (!e.shiftKey && document.activeElement === last) {
        first.focus();
        e.preventDefault();
    }
}

function openApproveModal(id, empName, type) {
    lastLeaveTrigger = document.activeElement;
    document.getElementById('approve-modal-target').innerText = `Setujui pengajuan ${type} atas nama ${empName}?`;
    document.getElementById('approve-form').action = `/admin/leave-requests/${id}/approve`;
    const modal = document.getElementById('modal-approve');
    modal.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('approve_reviewer_note')?.focus();
    }, 50);
}

function closeApproveModal() {
    document.getElementById('modal-approve').classList.add('hidden');
    if (lastLeaveTrigger && typeof lastLeaveTrigger.focus === 'function') {
        lastLeaveTrigger.focus();
        lastLeaveTrigger = null;
    }
}

function openRejectModal(id, empName, type) {
    lastLeaveTrigger = document.activeElement;
    document.getElementById('reject-modal-target').innerText = `Tolak pengajuan ${type} atas nama ${empName}?`;
    document.getElementById('reject-form').action = `/admin/leave-requests/${id}/reject`;
    const modal = document.getElementById('modal-reject');
    modal.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('reject_reviewer_note')?.focus();
    }, 50);
}

function closeRejectModal() {
    document.getElementById('modal-reject').classList.add('hidden');
    if (lastLeaveTrigger && typeof lastLeaveTrigger.focus === 'function') {
        lastLeaveTrigger.focus();
        lastLeaveTrigger = null;
    }
}

document.addEventListener('keydown', function(e) {
    const approveModal = document.getElementById('modal-approve');
    const rejectModal = document.getElementById('modal-reject');
    if (e.key === 'Escape') {
        if (approveModal && !approveModal.classList.contains('hidden')) closeApproveModal();
        if (rejectModal && !rejectModal.classList.contains('hidden')) closeRejectModal();
    } else if (approveModal && !approveModal.classList.contains('hidden')) {
        trapModalFocus(approveModal, e);
    } else if (rejectModal && !rejectModal.classList.contains('hidden')) {
        trapModalFocus(rejectModal, e);
    }
});

document.getElementById('modal-approve')?.addEventListener('click', function(e) {
    if (e.target === this) closeApproveModal();
});

document.getElementById('modal-reject')?.addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

// Double submit prevention
['approve-form', 'reject-form'].forEach(formId => {
    document.getElementById(formId)?.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        }
    });
});
</script>
@endsection
