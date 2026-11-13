@extends('layouts.admin')

@section('title', 'Kelola Pengajuan Lembur')
@section('page-title', 'Persetujuan & Riwayat Lembur')

@section('content')
<div class="space-y-6">

    <!-- Flash Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Terjadi Kesalahan Proses:</p>
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

    <!-- Header Card -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Persetujuan & Monitoring Lembur</h2>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola persetujuan pengajuan lembur, audit durasi, dan monitoring verifikasi sesi lembur karyawan.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-outlet-filter />
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
        <form action="{{ route('admin.overtime-requests.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <!-- Status Filter -->
            <div>
                <label for="filter_ot_status" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status</label>
                <select id="filter_ot_status" name="status" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden min-h-[44px] ui-select">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                    <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                    <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                    <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Dibatalkan (Cancelled)</option>
                </select>
            </div>

            <!-- Employee Filter -->
            <div>
                <label for="filter_ot_employee" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Karyawan</label>
                <select id="filter_ot_employee" name="employee_id" class="w-full min-w-0 max-w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden min-h-[44px] ui-select">
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
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Dari Tanggal</label>
                <x-date-input name="start_date" value="{{ $filters['start_date'] }}" wrapperClass="bg-slate-50 dark:bg-slate-950" />
            </div>

            <!-- End Date Filter -->
            <div class="w-full min-w-0 max-w-full">
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <x-date-input name="end_date" value="{{ $filters['end_date'] }}" wrapperClass="bg-slate-50 dark:bg-slate-950" />
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 text-white font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer min-h-[44px] shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.overtime-requests.index') }}" class="py-2.5 px-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold rounded-xl transition-all text-xs flex items-center justify-center cursor-pointer min-h-[44px]">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Daftar Pengajuan Lembur</h3>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">{{ count($requests) }} Pengajuan</span>
        </div>

        @if(count($requests) === 0)
            <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl m-5 bg-slate-50/50 dark:bg-slate-800/40">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Tidak ada pengajuan lembur yang ditemukan.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Coba sesuaikan filter atau rentang tanggal pencarian Anda.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Karyawan</th>
                            <th class="px-4 py-3.5">Tanggal Kerja</th>
                            <th class="px-4 py-3.5">Jadwal Shift</th>
                            <th class="px-4 py-3.5">Absensi Aktual</th>
                            <th class="px-4 py-3.5">Candidate Lembur</th>
                            <th class="px-4 py-3.5">Requested</th>
                            <th class="px-4 py-3.5">Approved</th>
                            <th class="px-4 py-3.5">Session</th>
                            <th class="px-4 py-3.5">Alasan</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($requests as $req)
                            @php
                                $dateKey = $req->employee_id . '_' . $req->work_date->format('Y-m-d');
                                $att = $attendances[$dateKey] ?? null;
                                $sch = $schedules[$dateKey] ?? null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <!-- Karyawan -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold flex items-center justify-center text-xs shrink-0 border border-slate-200 dark:border-slate-700">
                                            {{ substr($req->employee->full_name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 dark:text-slate-100">{{ $req->employee->full_name }}</div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $req->employee->employee_code }} • {{ $req->employee->jobTitle?->name ?? 'Karyawan' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Tanggal Kerja -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ $req->work_date->translatedFormat('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-sans">{{ $req->work_date->translatedFormat('l') }}</div>
                                </td>

                                <!-- Shift -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($sch && $sch->shift)
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $sch->shift->name }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ substr($sch->shift->start_time, 0, 5) }} - {{ substr($sch->shift->end_time, 0, 5) }}</div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 font-medium">-</span>
                                    @endif
                                </td>

                                <!-- Absensi Aktual -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-[11px]">
                                    @if($att)
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">In: {{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }} | Out: {{ $att->check_out_at ? $att->check_out_at->format('H:i') : '-' }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-sans">Durasi: {{ (int) floor(($att->worked_minutes ?? 0) / 60) }}j {{ ($att->worked_minutes ?? 0) % 60 }}m</div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 font-medium italic">Belum Ada Record</span>
                                    @endif
                                </td>

                                <!-- Candidate Lembur (from Attendance) -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($att && $att->overtime_minutes > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            {{ $att->overtime_minutes }}m
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 font-medium font-mono">0m</span>
                                    @endif
                                </td>

                                <!-- Requested Minutes -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="font-extrabold text-slate-900 dark:text-slate-100 font-mono">{{ $req->formatted_requested_duration }}</span>
                                </td>

                                <!-- Approved Minutes -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($req->status === 'approved')
                                        <span class="font-extrabold text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-lg border border-emerald-200 dark:border-emerald-800/60 font-mono">
                                            {{ $req->formatted_approved_duration }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 font-medium">-</span>
                                    @endif
                                </td>

                                <!-- Session -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-[11px]">
                                    @if(!$req->session)
                                        <span class="font-bold text-slate-400 dark:text-slate-500">Belum Dimulai</span>
                                    @elseif($req->session->isActive())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">Active</span>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">{{ $req->session->check_in_at?->format('d/m H:i') }}</div>
                                    @elseif($req->session->isCancelled())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">Cancelled</span>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">Credited 0m</div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">Completed</span>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ $req->session->check_in_at?->format('H:i') }}–{{ $req->session->check_out_at?->format('H:i') }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">Credited {{ \App\Models\OvertimeSession::formatMinutes($req->session->credited_minutes) }}</div>
                                    @endif
                                    @if($req->session?->corrected_at)
                                        <div class="mt-1 text-[9px] font-bold text-amber-700 dark:text-amber-400">Koreksi Admin · {{ $req->session->corrected_at->format('d/m H:i') }}</div>
                                    @endif
                                </td>

                                <!-- Alasan -->
                                <td class="px-4 py-3.5 max-w-xs">
                                    <p class="text-slate-700 dark:text-slate-300 font-medium line-clamp-2" title="{{ $req->reason }}">"{{ $req->reason }}"</p>
                                    @if($req->reviewer_note)
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 p-1.5 rounded-lg mt-1">
                                            <strong>Reviewer:</strong> {{ $req->reviewer_note }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Approve Modal Trigger -->
                                            <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', {{ $req->requested_minutes }})" class="min-h-[36px] px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-extrabold shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Setujui</span>
                                            </button>

                                            <!-- Reject Modal Trigger -->
                                            <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}')" class="min-h-[36px] px-3 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 rounded-xl text-[11px] font-extrabold transition-all cursor-pointer inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Tolak</span>
                                            </button>
                                        </div>
                                    @elseif($req->session?->isActive())
                                        <div class="flex flex-col items-end gap-1">
                                            <button type="button" data-mode="force" data-url="{{ route('admin.overtime-sessions.force-finish', $req->session) }}" data-start="{{ $req->session->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" onclick="openSessionModal(this)" class="min-h-[32px] rounded-lg bg-indigo-600 hover:bg-indigo-700 px-2.5 text-[11px] font-extrabold text-white cursor-pointer transition-colors">Force Finish</button>
                                            <button type="button" data-mode="cancel" data-url="{{ route('admin.overtime-sessions.cancel', $req->session) }}" onclick="openSessionModal(this)" class="min-h-[32px] rounded-lg border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 px-2.5 text-[11px] font-extrabold text-rose-700 dark:text-rose-300 cursor-pointer transition-colors">Cancel Session</button>
                                        </div>
                                    @elseif($req->session?->isCompleted())
                                        <button type="button" data-mode="correct" data-url="{{ route('admin.overtime-sessions.correct', $req->session) }}" data-start="{{ $req->session->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" data-finish="{{ $req->session->check_out_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" onclick="openSessionModal(this)" class="min-h-[32px] rounded-lg bg-amber-600 hover:bg-amber-700 px-3 text-[11px] font-extrabold text-white cursor-pointer transition-colors">Koreksi</button>
                                    @else
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
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

            <!-- Mobile Stacked Card View -->
            <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-800 p-4 space-y-4">
                @foreach($requests as $req)
                    @php
                        $dateKey = $req->employee_id . '_' . $req->work_date->format('Y-m-d');
                        $att = $attendances[$dateKey] ?? null;
                        $sch = $schedules[$dateKey] ?? null;
                    @endphp
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-extrabold flex items-center justify-center text-xs shrink-0 border border-slate-200 dark:border-slate-600">
                                    {{ substr($req->employee->full_name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-slate-100">{{ $req->employee->full_name }}</h4>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $req->employee->employee_code }} • {{ $req->work_date->translatedFormat('d M Y') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        <!-- Duration metrics grid -->
                        <div class="grid grid-cols-2 gap-2 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-xs font-mono">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase font-sans">Diajukan</span>
                                <p class="font-extrabold text-slate-900 dark:text-slate-100">{{ $req->formatted_requested_duration }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase font-sans">Disetujui</span>
                                <p class="font-extrabold text-emerald-700 dark:text-emerald-400">{{ $req->status === 'approved' ? $req->formatted_approved_duration : '-' }}</p>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-600 dark:text-slate-400">
                            <strong>Alasan:</strong> "{{ $req->reason }}"
                        </div>

                        @if($req->session)
                            <div class="p-2.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-[11px] space-y-1">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="text-slate-500 uppercase text-[10px]">Sesi Lembur:</span>
                                    <span class="{{ $req->session->isActive() ? 'text-indigo-600 dark:text-indigo-400 font-extrabold' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $req->session->isActive() ? 'Active' : ($req->session->isCancelled() ? 'Cancelled' : 'Completed') }}
                                    </span>
                                </div>
                                @if($req->session->check_in_at)
                                    <p class="font-mono text-[10px] text-slate-600 dark:text-slate-400">Waktu: {{ $req->session->check_in_at->format('H:i') }} {{ $req->session->check_out_at ? '– ' . $req->session->check_out_at->format('H:i') : '' }}</p>
                                @endif
                                @if($req->session->isCompleted())
                                    <p class="font-mono text-[10px] text-slate-600 dark:text-slate-400">Credited: {{ \App\Models\OvertimeSession::formatMinutes($req->session->credited_minutes) }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Mobile Action Buttons -->
                        <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                            @if($req->status === 'pending')
                                <button type="button" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}', {{ $req->requested_minutes }})" class="min-h-[44px] flex-1 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs flex items-center justify-center gap-1 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Setujui</span>
                                </button>
                                <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}')" class="min-h-[44px] flex-1 py-2 px-3 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 font-extrabold rounded-xl text-xs flex items-center justify-center gap-1 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Tolak</span>
                                </button>
                            @elseif($req->session?->isActive())
                                <button type="button" data-mode="force" data-url="{{ route('admin.overtime-sessions.force-finish', $req->session) }}" data-start="{{ $req->session->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" onclick="openSessionModal(this)" class="min-h-[44px] flex-1 rounded-xl bg-indigo-600 px-3 text-xs font-extrabold text-white">Force Finish</button>
                                <button type="button" data-mode="cancel" data-url="{{ route('admin.overtime-sessions.cancel', $req->session) }}" onclick="openSessionModal(this)" class="min-h-[44px] flex-1 rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 px-3 text-xs font-extrabold text-rose-700 dark:text-rose-300">Cancel Session</button>
                            @elseif($req->session?->isCompleted())
                                <button type="button" data-mode="correct" data-url="{{ route('admin.overtime-sessions.correct', $req->session) }}" data-start="{{ $req->session->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" data-finish="{{ $req->session->check_out_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" onclick="openSessionModal(this)" class="min-h-[44px] w-full rounded-xl bg-amber-600 px-4 text-xs font-extrabold text-white">Koreksi Sesi</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Session Recovery Modal -->
<div id="sessionRecoveryModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="sessionModalTitle">
    <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 id="sessionModalTitle" class="text-base font-extrabold text-slate-900 dark:text-slate-100">Recovery Sesi Lembur</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Evidence asli tidak akan diubah.</p>
            </div>
            <button type="button" onclick="closeSessionModal()" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
        </div>
        <form id="sessionRecoveryForm" method="POST" class="space-y-4 text-xs">
            @csrf
            <div id="sessionStartField" class="hidden min-w-0">
                <label for="sessionStartInput" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Waktu Mulai</label>
                <input id="sessionStartInput" type="datetime-local" name="check_in_at" class="min-h-[44px] w-full min-w-0 max-w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
            </div>
            <div id="sessionFinishField" class="hidden min-w-0">
                <label for="sessionFinishInput" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Waktu Selesai Aktual</label>
                <input id="sessionFinishInput" type="datetime-local" class="min-h-[44px] w-full min-w-0 max-w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
            </div>
            <div>
                <label for="sessionRecoveryReason" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Alasan <span class="text-rose-600 dark:text-rose-400">*</span></label>
                <textarea id="sessionRecoveryReason" name="reason" minlength="5" maxlength="2000" rows="3" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 p-3.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden" placeholder="Jelaskan dasar tindakan admin..."></textarea>
            </div>
            <p id="sessionRuleText" class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3 text-[11px] text-slate-600 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60"></p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end pt-2">
                <button type="button" onclick="closeSessionModal()" class="min-h-[44px] rounded-xl bg-slate-100 dark:bg-slate-800 px-4 font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">Batal</button>
                <button id="sessionSubmitButton" type="submit" class="min-h-[44px] rounded-xl bg-slate-900 dark:bg-slate-700 px-5 font-extrabold text-white hover:bg-slate-800 dark:hover:bg-slate-600 shadow-xs cursor-pointer">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Approve Overtime -->
<div id="approveModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden" role="dialog" aria-modal="true" aria-labelledby="approveModalHeading">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="approveModalHeading" class="text-base font-extrabold text-slate-900 dark:text-slate-100">Setujui Pengajuan Lembur</h3>
            <button type="button" onclick="closeApproveModal()" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
        </div>

        <form id="approveForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                <p class="text-slate-600 dark:text-slate-400 font-medium">Karyawan: <strong id="approve_emp_name" class="text-slate-900 dark:text-slate-100"></strong></p>
                <p class="text-slate-600 dark:text-slate-400 font-medium mt-1">Durasi Diajukan: <strong id="approve_req_mins" class="text-rose-600 dark:text-rose-400 font-mono"></strong></p>
            </div>

            <div>
                <label for="approved_minutes" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Durasi Disetujui (Menit) <span class="text-rose-600 dark:text-rose-400">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="approved_minutes" id="approved_minutes" min="0" required class="min-h-[44px] flex-1 px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-extrabold text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden font-mono">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Menit</span>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Anda dapat menyetujui menit yang sama atau lebih sedikit dari yang diajukan.</p>
            </div>

            <div>
                <label for="approve_ot_reviewer_note" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Reviewer (Opsional)</label>
                <textarea id="approve_ot_reviewer_note" name="reviewer_note" rows="2" placeholder="Catatan persetujuan..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeApproveModal()" class="min-h-[44px] px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl">Batal</button>
                <button type="submit" class="min-h-[44px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-xs cursor-pointer">Setujui Lembur</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject Overtime -->
<div id="rejectModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden" role="dialog" aria-modal="true" aria-labelledby="rejectModalHeading">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="rejectModalHeading" class="text-base font-extrabold text-slate-900 dark:text-slate-100">Tolak Pengajuan Lembur</h3>
            <button type="button" onclick="closeRejectModal()" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
        </div>

        <form id="rejectForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                <p class="text-slate-600 dark:text-slate-400 font-medium">Karyawan: <strong id="reject_emp_name" class="text-slate-900 dark:text-slate-100"></strong></p>
            </div>

            <div>
                <label for="reject_ot_reviewer_note" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Alasan Penolakan <span class="text-rose-600 dark:text-rose-400">*</span></label>
                <textarea id="reject_ot_reviewer_note" name="reviewer_note" rows="3" required placeholder="Jelaskan alasan penolakan pengajuan lembur ini..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRejectModal()" class="min-h-[44px] px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl">Batal</button>
                <button type="submit" class="min-h-[44px] px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl shadow-xs ui-btn ui-btn-primary cursor-pointer">Tolak Lembur</button>
            </div>
        </form>
    </div>
</div>

<script>
    let lastOvertimeTrigger = null;

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

    function openApproveModal(id, empName, reqMins) {
        lastOvertimeTrigger = document.activeElement;
        document.getElementById('approve_emp_name').innerText = empName;
        document.getElementById('approve_req_mins').innerText = `${reqMins} menit`;
        document.getElementById('approved_minutes').value = reqMins;
        document.getElementById('approved_minutes').max = reqMins;
        document.getElementById('approveForm').action = `/admin/overtime-requests/${id}/approve`;
        const modal = document.getElementById('approveModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('approved_minutes')?.focus();
        }, 50);
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
        if (lastOvertimeTrigger && typeof lastOvertimeTrigger.focus === 'function') {
            lastOvertimeTrigger.focus();
            lastOvertimeTrigger = null;
        }
    }

    function openRejectModal(id, empName) {
        lastOvertimeTrigger = document.activeElement;
        document.getElementById('reject_emp_name').innerText = empName;
        document.getElementById('rejectForm').action = `/admin/overtime-requests/${id}/reject`;
        const modal = document.getElementById('rejectModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('reject_ot_reviewer_note')?.focus();
        }, 50);
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        if (lastOvertimeTrigger && typeof lastOvertimeTrigger.focus === 'function') {
            lastOvertimeTrigger.focus();
            lastOvertimeTrigger = null;
        }
    }

    function openSessionModal(button) {
        lastOvertimeTrigger = button;
        const mode = button.dataset.mode;
        const modal = document.getElementById('sessionRecoveryModal');
        const form = document.getElementById('sessionRecoveryForm');
        const startField = document.getElementById('sessionStartField');
        const finishField = document.getElementById('sessionFinishField');
        const finishInput = document.getElementById('sessionFinishInput');
        const startInput = document.getElementById('sessionStartInput');
        form.action = button.dataset.url;
        form.reset();
        startField.classList.toggle('hidden', mode !== 'correct');
        finishField.classList.toggle('hidden', mode === 'cancel');
        startInput.required = mode === 'correct';
        finishInput.required = mode !== 'cancel';
        finishInput.name = mode === 'force' ? 'finish_at' : (mode === 'correct' ? 'check_out_at' : '');
        startInput.value = button.dataset.start || '';
        finishInput.value = button.dataset.finish || '';
        document.getElementById('sessionModalTitle').textContent = mode === 'force' ? 'Force Finish Lembur' : (mode === 'cancel' ? 'Cancel Session Lembur' : 'Koreksi Sesi Completed');
        document.getElementById('sessionRuleText').textContent = mode === 'cancel'
            ? 'Sesi tidak dihapus. Actual dan credited ditetapkan 0 menit.'
            : 'Actual dihitung ulang dan credited dibatasi approved minutes.';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            document.getElementById('sessionRecoveryReason')?.focus();
        }, 50);
    }

    function closeSessionModal() {
        const modal = document.getElementById('sessionRecoveryModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (lastOvertimeTrigger && typeof lastOvertimeTrigger.focus === 'function') {
            lastOvertimeTrigger.focus();
            lastOvertimeTrigger = null;
        }
    }

    document.addEventListener('keydown', function(e) {
        const approveModal = document.getElementById('approveModal');
        const rejectModal = document.getElementById('rejectModal');
        const sessionModal = document.getElementById('sessionRecoveryModal');
        if (e.key === 'Escape') {
            if (approveModal && !approveModal.classList.contains('hidden')) closeApproveModal();
            if (rejectModal && !rejectModal.classList.contains('hidden')) closeRejectModal();
            if (sessionModal && !sessionModal.classList.contains('hidden')) closeSessionModal();
        } else if (approveModal && !approveModal.classList.contains('hidden')) {
            trapModalFocus(approveModal, e);
        } else if (rejectModal && !rejectModal.classList.contains('hidden')) {
            trapModalFocus(rejectModal, e);
        } else if (sessionModal && !sessionModal.classList.contains('hidden')) {
            trapModalFocus(sessionModal, e);
        }
    });

    document.getElementById('sessionRecoveryModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeSessionModal();
    });

    document.getElementById('approveModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeApproveModal();
    });

    document.getElementById('rejectModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeRejectModal();
    });

    // Double submit prevention
    ['approveForm', 'rejectForm', 'sessionRecoveryForm'].forEach(formId => {
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
