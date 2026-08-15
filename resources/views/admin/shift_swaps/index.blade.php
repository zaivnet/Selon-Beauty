@extends('layouts.admin')

@section('title', 'Persetujuan Tukar Jadwal')
@section('page-title', 'Persetujuan Tukar Jadwal')

@section('content')
<div class="space-y-5" x-data="{ rejectModal: false, swapId: null, reqName: '', targetName: '' }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-black tracking-tight text-slate-950 md:text-2xl">Permintaan Pertukaran Jadwal Kerja</h2>
            <p class="mt-1 text-xs text-slate-500">Verifikasi dan setujui penukaran jadwal antar karyawan secara aman.</p>
        </div>
        @if($pendingAdminCount > 0)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-800">
                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $pendingAdminCount }} Menunggu Persetujuan Admin
            </span>
        @endif
    </div>

    <!-- Filters Section -->
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs md:p-5">
        <form action="{{ route('admin.shift-swaps.index') }}" method="GET" class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-status" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Status</label>
                <select id="filter-status" name="status" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    <option value="pending_admin" @selected($statusFilter === 'pending_admin')>Menunggu Admin (Default)</option>
                    <option value="pending_target" @selected($statusFilter === 'pending_target')>Menunggu Rekan</option>
                    <option value="approved" @selected($statusFilter === 'approved')>Disetujui</option>
                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak (Rekan/Admin)</option>
                    <option value="cancelled" @selected($statusFilter === 'cancelled')>Dibatalkan</option>
                    <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                </select>
            </div>
            <div>
                <label for="filter-employee" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Karyawan</label>
                <select id="filter-employee" name="employee_id" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua Karyawan</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($employeeFilter === $emp->id)>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-date" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Tanggal</label>
                <input type="date" id="filter-date" name="date" value="{{ $dateFilter }}" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
            </div>
            <div class="flex gap-2 self-end">
                <button type="submit" class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-slate-900 px-4 font-extrabold text-white transition hover:bg-slate-800">
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.shift-swaps.index') }}" class="flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 px-4 font-extrabold text-slate-700 hover:bg-slate-200">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <!-- Swap Requests Table & Mobile View -->
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        @if($swaps->isEmpty())
            <div class="px-5 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <p class="mt-3 text-sm font-black text-slate-800">Tidak ada permintaan tukar jadwal</p>
                <p class="mt-1 text-xs text-slate-500">Coba atur ulang filter pencarian Anda.</p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1000px] text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Tanggal Diajukan</th>
                            <th class="px-3 py-3">Pemohon (Ayu)</th>
                            <th class="px-3 py-3">Tujuan (Dia)</th>
                            <th class="px-3 py-3">Tanggal Jadwal</th>
                            <th class="px-3 py-3">Arah Penukaran Shift</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($swaps as $swap)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-mono text-slate-500">{{ $swap->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3 font-bold text-slate-900">{{ $swap->requester?->full_name }}</td>
                                <td class="px-3 py-3 font-bold text-slate-900">{{ $swap->target?->full_name }}</td>
                                <td class="px-3 py-3 font-mono font-bold text-slate-800">{{ $swap->requester_work_date->format('d/m/Y') }}</td>
                                <td class="px-3 py-3">
                                    <div class="text-[11px] leading-tight">
                                        <span class="font-bold text-slate-800">{{ $swap->requesterShift?->name }}</span>
                                        <span class="text-slate-400">&rarr;</span>
                                        <span class="font-bold text-indigo-700">{{ $swap->targetShift?->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black uppercase {{ $swap->status_badge_class }}">
                                        {{ $swap->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right space-x-1">
                                    <a href="{{ route('admin.shift-swaps.show', $swap) }}" class="inline-flex min-h-[36px] items-center rounded-lg bg-slate-100 px-3 text-[11px] font-extrabold text-slate-700 hover:bg-slate-200">
                                        Review
                                    </a>
                                    @if($swap->isPendingAdmin())
                                        <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" class="inline" onsubmit="return confirm('Setujui pertukaran jadwal ini? Jadwal efektif kedua karyawan akan langsung ditukar.')">
                                            @csrf
                                            <button class="min-h-[36px] rounded-lg bg-emerald-600 px-3 text-[11px] font-black text-white hover:bg-emerald-700">
                                                Setujui
                                            </button>
                                        </form>
                                        <button type="button" @click="rejectModal = true; swapId = {{ $swap->id }}; reqName = '{{ $swap->requester?->full_name }}'; targetName = '{{ $swap->target?->full_name }}'" class="min-h-[36px] rounded-lg border border-rose-200 bg-rose-50 px-3 text-[11px] font-black text-rose-700 hover:bg-rose-100">
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
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($swaps as $swap)
                    <article class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400">Tukar Jadwal #{{ $swap->id }}</span>
                                <h3 class="text-sm font-black text-slate-900">{{ $swap->requester?->full_name }} &leftrightarrow; {{ $swap->target?->full_name }}</h3>
                                <p class="text-xs font-mono font-bold text-slate-700">Tanggal: {{ $swap->requester_work_date->translatedFormat('d F Y') }}</p>
                            </div>
                            <span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black uppercase {{ $swap->status_badge_class }}">
                                {{ $swap->status_label }}
                            </span>
                        </div>

                        <!-- Stacked Preview Cards -->
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs space-y-2">
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">{{ $swap->requester?->full_name }} (Pemohon)</span>
                                <span class="font-bold text-slate-800">{{ $swap->requesterShift?->name }} &rarr; <span class="text-indigo-700">{{ $swap->targetShift?->name }}</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ $swap->target?->full_name }} (Tujuan)</span>
                                <span class="font-bold text-slate-800">{{ $swap->targetShift?->name }} &rarr; <span class="text-indigo-700">{{ $swap->requesterShift?->name }}</span></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <a href="{{ route('admin.shift-swaps.show', $swap) }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-slate-100 text-xs font-extrabold text-slate-700">
                                Lihat Pratinjau
                            </a>
                            @if($swap->isPendingAdmin())
                                <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" class="flex-1" onsubmit="return confirm('Setujui pertukaran jadwal ini?')">
                                    @csrf
                                    <button class="flex min-h-[44px] w-full items-center justify-center rounded-xl bg-emerald-600 text-xs font-black text-white hover:bg-emerald-700">
                                        Setujui
                                    </button>
                                </form>
                                <button type="button" @click="rejectModal = true; swapId = {{ $swap->id }}; reqName = '{{ $swap->requester?->full_name }}'; targetName = '{{ $swap->target?->full_name }}'" class="flex min-h-[44px] items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 text-xs font-black text-rose-700 hover:bg-rose-100">
                                    Tolak
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 bg-slate-50 p-4">{{ $swaps->links() }}</div>
        @endif
    </section>

    <!-- Reject Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div @click.away="rejectModal = false" class="w-full max-w-md space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900">Tolak Permintaan Tukar Jadwal</h3>
                <button type="button" @click="rejectModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <p class="text-xs text-slate-600">
                Tolak permohonan tukar jadwal antara <strong class="font-bold text-slate-900" x-text="reqName"></strong> dan <strong class="font-bold text-slate-900" x-text="targetName"></strong>.
            </p>
            <form :action="'{{ url('admin/shift-swaps') }}/' + swapId + '/reject'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="admin-reject-reason" class="mb-1 block text-xs font-black text-slate-700">Alasan Penolakan <span class="text-rose-600">*</span></label>
                    <textarea id="admin-reject-reason" name="reason" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan admin..." class="w-full rounded-xl border-slate-300 text-xs focus:border-rose-500 focus:ring-rose-500"></textarea>
                    <p class="mt-1 text-[10px] text-slate-500">Minimal 5 karakter wajib diisi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="rejectModal = false" class="min-h-[44px] rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-xl bg-rose-600 px-5 text-xs font-black text-white hover:bg-rose-700">Tolak Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
