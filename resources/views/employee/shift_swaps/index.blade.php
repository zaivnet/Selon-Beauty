@extends('layouts.employee')

@section('title', 'Tukar Jadwal')

@section('content')
<div class="space-y-4 md:space-y-5">
    <!-- Navigation Tabs -->
    <div class="flex bg-slate-200/80 dark:bg-slate-900 p-1 rounded-xl gap-1 text-xs font-bold border border-transparent dark:border-slate-800">
        <a href="{{ route('employee.leave-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.leave-requests.*') ? 'bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Izin / Sakit / Cuti
        </a>
        <a href="{{ route('employee.overtime-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.overtime-requests.*') ? 'bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Lembur
        </a>
        @if(auth()->user()?->role !== 'superadmin' && auth()->user()?->employee?->attendance_enabled !== false)
            @php
                $pendingSwapCount = \App\Models\ShiftSwapRequest::where('target_employee_id', auth()->user()?->employee_id)->where('status', 'pending_target')->count();
            @endphp
            <a href="{{ route('employee.shift-swaps.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all flex items-center justify-center gap-1 {{ request()->routeIs('employee.shift-swaps.*') ? 'bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
                <span>Tukar Jadwal</span>
                @if($pendingSwapCount > 0)
                    <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[9px] font-black text-white leading-none">{{ $pendingSwapCount }}</span>
                @endif
            </a>
        @endif
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-950 dark:text-slate-100 md:text-2xl">Pertukaran Jadwal Kerja</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ajukan atau kelola penukaran shift dengan rekan kerja secara aman.</p>
        </div>
        <a href="{{ route('employee.shift-swaps.create') }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-slate-900 dark:bg-rose-600 px-5 text-xs font-black text-white shadow-xs hover:bg-slate-800 dark:hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-slate-900 ui-btn ui-btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Tukar Jadwal
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex space-x-6">
            <a href="{{ route('employee.shift-swaps.index', ['tab' => 'my_requests']) }}" class="border-b-2 py-3 text-xs font-black transition {{ $tab === 'my_requests' ? 'border-slate-900 dark:border-rose-500 text-slate-950 dark:text-slate-100' : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200' }}">
                Permintaan Saya
            </a>
            <a href="{{ route('employee.shift-swaps.index', ['tab' => 'incoming']) }}" class="border-b-2 py-3 text-xs font-black transition flex items-center gap-2 {{ $tab === 'incoming' ? 'border-slate-900 dark:border-rose-500 text-slate-950 dark:text-slate-100' : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200' }}">
                Permintaan Masuk
                @if($incomingRequests->where('status', 'pending_target')->count() > 0)
                    <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-black text-white">{{ $incomingRequests->where('status', 'pending_target')->count() }}</span>
                @endif
            </a>
        </nav>
    </div>

    @if($tab === 'my_requests')
        <section class="space-y-3">
            @if($myRequests->isEmpty())
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-200">Belum ada permohonan</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Anda belum pernah mengajukan penukaran jadwal.</p>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($myRequests as $swap)
                        <article class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                            {{ $swap->status_label }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500">Diajukan {{ $swap->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                                        Tukar dengan <span class="text-indigo-600 dark:text-indigo-400">{{ $swap->target?->full_name }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Tanggal: <strong class="font-bold text-slate-800 dark:text-slate-200">{{ $swap->requester_work_date->translatedFormat('d F Y') }}</strong>
                                        @if($swap->requester_work_date->ne($swap->target_work_date))
                                            &rarr; <strong class="font-bold text-slate-800 dark:text-slate-200">{{ $swap->target_work_date->translatedFormat('d F Y') }}</strong>
                                        @endif
                                    </p>
                                </div>

                                @if($swap->isPendingTarget() || $swap->isPendingAdmin())
                                    <form action="{{ route('employee.shift-swaps.cancel', $swap) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan tukar jadwal ini?')">
                                        @csrf
                                        <button class="flex min-h-[44px] w-full items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/50 px-4 text-xs font-extrabold text-rose-700 dark:text-rose-300 transition hover:bg-rose-100 dark:hover:bg-rose-900/50 sm:w-auto">
                                            Batalkan
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Comparison preview stacked cards -->
                            <div class="mt-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3 text-xs sm:grid-cols-2">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500">Shift Saya Asli</span>
                                    <p class="font-extrabold text-slate-800 dark:text-slate-200">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-indigo-500 dark:text-indigo-400">Shift Rekan Akan Diterima</span>
                                    <p class="font-extrabold text-indigo-900 dark:text-indigo-200">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                                </div>
                            </div>

                            @if($swap->requester_reason)
                                <p class="mt-2 text-xs italic text-slate-500 dark:text-slate-400">Alasan saya: "{{ $swap->requester_reason }}"</p>
                            @endif
                            @if($swap->target_response_reason)
                                <p class="mt-1 text-xs italic text-slate-600 dark:text-slate-400">Catatan rekan: "{{ $swap->target_response_reason }}"</p>
                            @endif
                            @if($swap->admin_response_reason)
                                <p class="mt-1 text-xs italic text-slate-600 dark:text-slate-400">Catatan admin: "{{ $swap->admin_response_reason }}"</p>
                            @endif
                        </article>
                    @endforeach
                </div>
                <div class="mt-4">{{ $myRequests->links() }}</div>
            @endif
        </section>
    @else
        <section class="space-y-3">
            @if($incomingRequests->isEmpty())
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-200">Tidak ada permohonan masuk</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Belum ada rekan yang mengajukan penukaran jadwal dengan Anda.</p>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($incomingRequests as $swap)
                        <article class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                            {{ $swap->status_label }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500">Diajukan {{ $swap->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                                        Permintaan dari <span class="text-rose-600 dark:text-rose-400">{{ $swap->requester?->full_name }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Tanggal: <strong class="font-bold text-slate-800 dark:text-slate-200">{{ $swap->target_work_date->translatedFormat('d F Y') }}</strong>
                                    </p>
                                </div>

                                @if($swap->isPendingTarget())
                                    <div class="flex gap-2">
                                        <button type="button"
                                            class="btn-swap-respond flex min-h-[44px] items-center justify-center gap-1 rounded-xl bg-emerald-600 px-4 text-xs font-black text-white hover:bg-emerald-700"
                                            data-respond-url="{{ route('employee.shift-swaps.respond', $swap) }}"
                                            data-action="accept"
                                            data-requester-name="{{ e($swap->requester?->full_name) }}"
                                            data-work-date="{{ e($swap->target_work_date->translatedFormat('d F Y')) }}">
                                            Terima
                                        </button>
                                        <button type="button"
                                            class="btn-swap-respond flex min-h-[44px] items-center justify-center gap-1 rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/50 px-4 text-xs font-black text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50"
                                            data-respond-url="{{ route('employee.shift-swaps.respond', $swap) }}"
                                            data-action="reject"
                                            data-requester-name="{{ e($swap->requester?->full_name) }}"
                                            data-work-date="{{ e($swap->target_work_date->translatedFormat('d F Y')) }}">
                                            Tolak
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Comparison preview stacked cards -->
                            <div class="mt-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3 text-xs sm:grid-cols-2">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500">Shift Rekan ({{ $swap->requester?->full_name }})</span>
                                    <p class="font-extrabold text-slate-800 dark:text-slate-200">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-indigo-500 dark:text-indigo-400">Shift Saya Saat Ini</span>
                                    <p class="font-extrabold text-indigo-900 dark:text-indigo-200">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                                </div>
                            </div>

                            @if($swap->requester_reason)
                                <p class="mt-2 text-xs italic text-slate-500 dark:text-slate-400">Alasan rekan: "{{ $swap->requester_reason }}"</p>
                            @endif
                        </article>
                    @endforeach
                </div>
                <div class="mt-4">{{ $incomingRequests->links() }}</div>
            @endif
        </section>
    @endif

    <!-- Respond Modal (Vanilla JS Powered - Zero Alpine Dependency) -->
    <div id="swap-modal-backdrop" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs hidden" role="dialog" aria-modal="true" aria-labelledby="swap-modal-title">
        <div id="swap-modal-card" class="w-full max-w-md space-y-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 id="swap-modal-title" class="text-base font-black text-slate-900 dark:text-slate-100">Respon Tukar Jadwal</h3>
                <button type="button" id="btn-swap-modal-close" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 min-h-[44px] min-w-[44px] flex items-center justify-center text-lg font-bold" aria-label="Tutup Modal">&times;</button>
            </div>
            <p id="swap-modal-desc" class="text-xs text-slate-600 dark:text-slate-400"></p>
            <form id="swap-modal-form" action="" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" id="swap-modal-action" name="action" value="">
                <div>
                    <label for="swap-modal-reason" class="mb-1 block text-xs font-black text-slate-700 dark:text-slate-300">
                        Alasan / Catatan Respon <span id="swap-modal-reason-required" class="text-rose-600 hidden">*</span>
                    </label>
                    <textarea id="swap-modal-reason" name="reason" rows="3" placeholder="Tuliskan catatan alasan..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 text-xs focus:border-slate-900 dark:focus:border-rose-500 focus:ring-slate-900 dark:focus:ring-rose-500 ui-input"></textarea>
                    <p id="swap-modal-reason-help" class="mt-1 text-[10px] text-slate-500 dark:text-slate-400 hidden">Wajib diisi minimal 5 karakter jika menolak.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="btn-swap-modal-cancel" class="min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-4 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" id="btn-swap-modal-submit" class="min-h-[44px] rounded-xl px-5 text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700">
                        <span>Kirim Respon</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const backdrop = document.getElementById('swap-modal-backdrop');
    const card = document.getElementById('swap-modal-card');
    const closeBtn = document.getElementById('btn-swap-modal-close');
    const cancelBtn = document.getElementById('btn-swap-modal-cancel');
    const form = document.getElementById('swap-modal-form');
    const titleEl = document.getElementById('swap-modal-title');
    const descEl = document.getElementById('swap-modal-desc');
    const actionInput = document.getElementById('swap-modal-action');
    const submitBtn = document.getElementById('btn-swap-modal-submit');
    const reasonInput = document.getElementById('swap-modal-reason');
    const reasonReq = document.getElementById('swap-modal-reason-required');
    const reasonHelp = document.getElementById('swap-modal-reason-help');

    function openModal(url, action, requesterName, workDate) {
        form.action = url;
        actionInput.value = action;
        reasonInput.value = '';

        if (action === 'accept') {
            titleEl.textContent = 'Terima Penukaran Jadwal';
            descEl.textContent = `Anda akan menyetujui penukaran jadwal dari ${requesterName} untuk tanggal ${workDate}. Permohonan selanjutnya akan diteruskan ke Admin / Owner untuk persetujuan akhir.`;
            submitBtn.className = 'min-h-[44px] rounded-xl px-5 text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700 cursor-pointer';
            submitBtn.querySelector('span').textContent = 'Ya, Terima Penukaran';
            reasonReq.classList.add('hidden');
            reasonHelp.classList.add('hidden');
            reasonInput.removeAttribute('required');
            reasonInput.removeAttribute('minlength');
        } else {
            titleEl.textContent = 'Tolak Penukaran Jadwal';
            descEl.textContent = `Anda akan menolak penukaran jadwal dari ${requesterName} untuk tanggal ${workDate}.`;
            submitBtn.className = 'min-h-[44px] rounded-xl px-5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 cursor-pointer';
            submitBtn.querySelector('span').textContent = 'Ya, Tolak Penukaran';
            reasonReq.classList.remove('hidden');
            reasonHelp.classList.remove('hidden');
            reasonInput.setAttribute('required', 'required');
            reasonInput.setAttribute('minlength', '5');
        }

        backdrop.classList.remove('hidden');
    }

    function closeModal() {
        backdrop.classList.add('hidden');
    }

    document.querySelectorAll('.btn-swap-respond').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(
                btn.getAttribute('data-respond-url'),
                btn.getAttribute('data-action'),
                btn.getAttribute('data-requester-name'),
                btn.getAttribute('data-work-date')
            );
        });
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            closeModal();
        }
    });
});
</script>
@endsection
