@extends('layouts.admin')

@section('title', 'Persetujuan Tukar Jadwal')
@section('page-title', 'Persetujuan Tukar Jadwal')

@section('content')
<div class="space-y-6" x-data="{ rejectModal: false, swapId: null, reqName: '', targetName: '' }" @keydown.escape.window="rejectModal = false">
    <!-- Header & Action Summary -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 md:text-2xl">Permintaan Pertukaran Jadwal Kerja</h2>
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Verifikasi, audit dan setujui penukaran jadwal antar karyawan secara aman dan multi-outlet.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-outlet-filter />
            @if($pendingAdminCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 px-3 py-1 text-xs font-black text-amber-800 dark:text-amber-300">
                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $pendingAdminCount }} Menunggu Persetujuan Admin</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Filters Section -->
    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs transition-colors">
        <form action="{{ route('admin.shift-swaps.index') }}" method="GET" class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-status" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Status</label>
                <select id="filter-status" name="status" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden ui-select">
                    <option value="pending_admin" @selected($statusFilter === 'pending_admin')>Menunggu Admin (Default)</option>
                    <option value="pending_target" @selected($statusFilter === 'pending_target')>Menunggu Rekan</option>
                    <option value="approved" @selected($statusFilter === 'approved')>Disetujui</option>
                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak (Rekan/Admin)</option>
                    <option value="cancelled" @selected($statusFilter === 'cancelled')>Dibatalkan</option>
                    <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                </select>
            </div>
            <div>
                <label for="filter-employee" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Karyawan</label>
                <select id="filter-employee" name="employee_id" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden ui-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($employeeFilter === $emp->id)>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-date" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Tanggal</label>
                <input type="date" id="filter-date" name="date" value="{{ $dateFilter }}" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
            </div>
            <div class="flex gap-2 self-end">
                <button type="submit" class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-slate-900 dark:bg-slate-700 px-4 font-extrabold text-white transition hover:bg-slate-800 dark:hover:bg-slate-600 cursor-pointer shadow-xs gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.shift-swaps.index') }}" class="flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 px-4 font-extrabold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <!-- Swap Requests Table & Mobile View -->
    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Daftar Pertukaran Shift</h3>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">{{ $swaps->total() }} Permintaan</span>
        </div>

        @if($swaps->isEmpty())
            <div class="px-5 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mb-3">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <p class="text-sm font-black text-slate-800 dark:text-slate-200">Tidak ada permintaan tukar jadwal</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Coba atur ulang filter pencarian Anda.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden overflow-x-auto md:block ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60 text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal Diajukan</th>
                            <th class="px-4 py-3.5">Pemohon</th>
                            <th class="px-4 py-3.5">Tujuan</th>
                            <th class="px-4 py-3.5">Tanggal Jadwal</th>
                            <th class="px-4 py-3.5">Arah Penukaran Shift</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($swaps as $swap)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3.5 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $swap->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold flex items-center justify-center text-[11px] shrink-0">
                                            {{ substr($swap->requester?->full_name ?? '?', 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 dark:text-slate-100">
                                                {{ $swap->requester?->full_name ?? 'Karyawan tidak ditemukan' }}
                                                @if($swap->requester?->trashed())
                                                    <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400">(Dihapus)</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $swap->requester?->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-extrabold flex items-center justify-center text-[11px] shrink-0">
                                            {{ substr($swap->target?->full_name ?? '?', 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 dark:text-slate-100">
                                                {{ $swap->target?->full_name ?? 'Karyawan tidak ditemukan' }}
                                                @if($swap->target?->trashed())
                                                    <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400">(Dihapus)</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $swap->target?->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">{{ $swap->requester_work_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="text-[11px] flex items-center gap-1.5 leading-tight">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">{{ $swap->requesterShift?->name }}</span>
                                        <span class="text-slate-400 dark:text-slate-500 font-bold">&rarr;</span>
                                        <span class="font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-md">{{ $swap->targetShift?->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                        {{ $swap->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                                    <a href="{{ route('admin.shift-swaps.show', $swap) }}" class="inline-flex min-h-[36px] items-center rounded-xl bg-slate-100 dark:bg-slate-800 px-3 text-[11px] font-extrabold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                        Review
                                    </a>
                                    @if($swap->isPendingAdmin())
                                        <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" class="inline" onsubmit="return confirm('Setujui pertukaran jadwal ini? Jadwal efektif kedua karyawan akan langsung ditukar.')">
                                            @csrf
                                            <button class="min-h-[36px] rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3 text-[11px] font-black text-white cursor-pointer shadow-2xs transition">
                                                Setujui
                                            </button>
                                        </form>
                                        <button type="button" @click="rejectModal = true; swapId = {{ $swap->id }}; reqName = '{{ addslashes($swap->requester?->full_name ?? '') }}'; targetName = '{{ addslashes($swap->target?->full_name ?? '') }}'" class="min-h-[36px] rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 px-3 text-[11px] font-black text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 cursor-pointer transition">
                                            Tolak
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked View -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden p-4 space-y-4">
                @foreach($swaps as $swap)
                    <article class="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 font-mono">ID: #{{ $swap->id }} • {{ $swap->created_at->format('d/m/Y') }}</span>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mt-0.5">{{ $swap->requester?->full_name }} &leftrightarrow; {{ $swap->target?->full_name }}</h3>
                                <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300 mt-0.5">Tanggal: {{ $swap->requester_work_date->translatedFormat('d F Y') }}</p>
                            </div>
                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                {{ $swap->status_label }}
                            </span>
                        </div>

                        <!-- Stacked Preview Cards -->
                        <div class="rounded-xl border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-900 p-3 text-xs space-y-2">
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                                <span class="text-slate-500 dark:text-slate-400">{{ $swap->requester?->full_name }} (Pemohon)</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $swap->requesterShift?->name }} &rarr; <span class="text-indigo-700 dark:text-indigo-400">{{ $swap->targetShift?->name }}</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">{{ $swap->target?->full_name }} (Tujuan)</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $swap->targetShift?->name }} &rarr; <span class="text-indigo-700 dark:text-indigo-400">{{ $swap->requesterShift?->name }}</span></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <a href="{{ route('admin.shift-swaps.show', $swap) }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-extrabold text-slate-700 dark:text-slate-300">
                                Review
                            </a>
                            @if($swap->isPendingAdmin())
                                <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" class="flex-1" onsubmit="return confirm('Setujui pertukaran jadwal ini?')">
                                    @csrf
                                    <button class="flex min-h-[44px] w-full items-center justify-center rounded-xl bg-emerald-600 text-xs font-black text-white hover:bg-emerald-700">
                                        Setujui
                                    </button>
                                </form>
                                <button type="button" @click="rejectModal = true; swapId = {{ $swap->id }}; reqName = '{{ addslashes($swap->requester?->full_name ?? '') }}'; targetName = '{{ addslashes($swap->target?->full_name ?? '') }}'" class="flex min-h-[44px] items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 px-4 text-xs font-black text-rose-700 dark:text-rose-300 hover:bg-rose-100">
                                    Tolak
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-4">{{ $swaps->links() }}</div>
        @endif
    </section>

    <!-- Reject Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="adminRejectSwapTitle" x-effect="if (rejectModal) { $nextTick(() => document.getElementById('admin-reject-reason')?.focus()) }">
        <div @click.away="rejectModal = false" class="w-full max-w-md space-y-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 id="adminRejectSwapTitle" class="text-base font-black text-slate-900 dark:text-slate-100">Tolak Permintaan Tukar Jadwal</h3>
                <button type="button" @click="rejectModal = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 text-lg font-bold" aria-label="Tutup Modal">&times;</button>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Tolak permohonan tukar jadwal antara <strong class="font-bold text-slate-900 dark:text-slate-100" x-text="reqName"></strong> dan <strong class="font-bold text-slate-900 dark:text-slate-100" x-text="targetName"></strong>.
            </p>
            <form :action="'{{ url('admin/shift-swaps') }}/' + swapId + '/reject'" method="POST" class="space-y-4" @submit="const b = $el.querySelector('button[type=submit]'); if (b) { b.disabled = true; b.classList.add('opacity-75', 'cursor-not-allowed'); }">
                @csrf
                <div>
                    <label for="admin-reject-reason" class="mb-1 block text-xs font-black text-slate-700 dark:text-slate-300">Alasan Penolakan <span class="text-rose-600 dark:text-rose-400">*</span></label>
                    <textarea id="admin-reject-reason" name="reason" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan admin..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-xs p-3.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Minimal 5 karakter wajib diisi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="rejectModal = false" class="min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-xl bg-rose-600 px-5 text-xs font-black text-white hover:bg-rose-700 ui-btn ui-btn-primary cursor-pointer">Tolak Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
