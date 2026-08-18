@extends('layouts.admin')

@section('title', 'Detail Permintaan Tukar Jadwal')
@section('page-title', 'Detail Tukar Jadwal')

@section('content')
<div class="space-y-5" x-data="{ rejectModal: false }">
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                    {{ $swap->status_label }}
                </span>
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500">Diajukan {{ $swap->created_at->translatedFormat('d F Y H:i') }}</span>
            </div>
            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-slate-100 md:text-2xl">Pertukaran Shift #{{ $swap->id }}</h2>
        </div>
        <a href="{{ route('admin.shift-swaps.index') }}" class="flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 px-4 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Kembali
        </a>
    </div>

    <!-- Direction Comparative Preview Card -->
    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="border-b border-slate-100 dark:border-slate-800 p-5">
            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Perbandingan Arah Penukaran Shift</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Periksa detail jadwal sebelum dan sesudah persetujuan admin.</p>
        </div>

        <div class="grid grid-cols-1 divide-y divide-slate-100 dark:divide-slate-800 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
            <!-- Requester Card -->
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500">Pemohon (Ayu)</span>
                        <h4 class="text-base font-black text-slate-900 dark:text-slate-100">
                            {{ $swap->requester?->full_name ?? 'Karyawan tidak ditemukan' }}
                            @if($swap->requester?->trashed())
                                <span class="ml-1 text-xs font-bold text-rose-600 dark:text-rose-400">(Dihapus)</span>
                            @endif
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $swap->requester?->employee_code ?? '—' }} · {{ $swap->requester?->jobTitle?->name ?? 'Karyawan' }}</p>
                    </div>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $swap->requester_work_date->translatedFormat('d F Y') }}</span>
                </div>

                <div class="space-y-2">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3 text-xs">
                        <span class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Jadwal Asli Saat Ini:</span>
                        <p class="mt-1 font-extrabold text-slate-900 dark:text-slate-100">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-xs">
                        <span class="text-[10px] font-black uppercase text-emerald-800 dark:text-emerald-400">Jadwal Baru Setelah Swap:</span>
                        <p class="mt-1 font-extrabold text-emerald-950 dark:text-emerald-300">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                    </div>
                </div>
            </div>

            <!-- Target Card -->
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-black uppercase text-indigo-600 dark:text-indigo-400">Rekan Tujuan (Dia)</span>
                        <h4 class="text-base font-black text-slate-900 dark:text-slate-100">
                            {{ $swap->target?->full_name ?? 'Karyawan tidak ditemukan' }}
                            @if($swap->target?->trashed())
                                <span class="ml-1 text-xs font-bold text-rose-600 dark:text-rose-400">(Dihapus)</span>
                            @endif
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $swap->target?->employee_code ?? '—' }} · {{ $swap->target?->jobTitle?->name ?? 'Karyawan' }}</p>
                    </div>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $swap->target_work_date->translatedFormat('d F Y') }}</span>
                </div>

                <div class="space-y-2">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3 text-xs">
                        <span class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Jadwal Asli Saat Ini:</span>
                        <p class="mt-1 font-extrabold text-slate-900 dark:text-slate-100">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-xs">
                        <span class="text-[10px] font-black uppercase text-emerald-800 dark:text-emerald-400">Jadwal Baru Setelah Swap:</span>
                        <p class="mt-1 font-extrabold text-emerald-950 dark:text-emerald-300">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reasons & History Details -->
    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs space-y-3 text-xs transition-colors">
        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Catatan & Riwayat Respon</h3>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                <dt class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Alasan Pemohon</dt>
                <dd class="mt-1 text-slate-800 dark:text-slate-200">{{ $swap->requester_reason ?: '—' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                <dt class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Respon Rekan Tujuan</dt>
                <dd class="mt-1 text-slate-800 dark:text-slate-200">
                    {{ $swap->target_responded_at ? $swap->target_responded_at->translatedFormat('d M Y H:i') : 'Belum merespon' }}
                    @if($swap->target_response_reason)<span class="block italic text-slate-500 dark:text-slate-400">"{{ $swap->target_response_reason }}"</span>@endif
                </dd>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                <dt class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Respon Admin</dt>
                <dd class="mt-1 text-slate-800 dark:text-slate-200">
                    {{ $swap->adminUser ? $swap->adminUser->name : '—' }}
                    @if($swap->admin_responded_at)<span class="block text-[10px] text-slate-500 dark:text-slate-400">{{ $swap->admin_responded_at->translatedFormat('d M Y H:i') }}</span>@endif
                    @if($swap->admin_response_reason)<span class="block italic text-slate-500 dark:text-slate-400">"{{ $swap->admin_response_reason }}"</span>@endif
                </dd>
            </div>
        </dl>

        @if($swap->isPendingAdmin())
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="rejectModal = true" class="flex min-h-[44px] items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 px-5 text-xs font-black text-rose-700 dark:text-rose-300 hover:bg-rose-100">
                    Tolak Permintaan
                </button>
                <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" onsubmit="return confirm('Setujui pertukaran jadwal ini? Override jadwal efektif akan langsung diterapkan.')">
                    @csrf
                    <button type="submit" class="flex min-h-[44px] items-center justify-center rounded-xl bg-emerald-600 px-6 text-xs font-black text-white hover:bg-emerald-700">
                        Setujui Tukar Jadwal
                    </button>
                </form>
            </div>
        @endif
    </section>

    <!-- Reject Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div @click.away="rejectModal = false" class="w-full max-w-md space-y-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100">Tolak Permintaan Tukar Jadwal</h3>
                <button type="button" @click="rejectModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">&times;</button>
            </div>
            <form action="{{ route('admin.shift-swaps.reject', $swap) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="reject-reason" class="mb-1 block text-xs font-black text-slate-700 dark:text-slate-300">Alasan Penolakan Admin <span class="text-rose-600 dark:text-rose-400">*</span></label>
                    <textarea id="reject-reason" name="reason" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:border-rose-500 focus:ring-rose-500 ui-input"></textarea>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Minimal 5 karakter wajib diisi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="rejectModal = false" class="min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-xl bg-rose-600 px-5 text-xs font-black text-white hover:bg-rose-700 ui-btn ui-btn-primary">Tolak Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
