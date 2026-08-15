@extends('layouts.employee')

@section('title', 'Tukar Jadwal')

@section('content')
<div class="space-y-4 md:space-y-5" x-data="{ respondModal: false, swapId: null, actionType: '', targetName: '', reqDate: '' }">
    <nav class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-xs" aria-label="Navigasi Jadwal">
        <a href="{{ route('employee.schedules.index') }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg px-3 text-[11px] font-extrabold text-slate-500 hover:text-slate-900">Jadwal Saya</a>
        <a href="{{ route('employee.shift-swaps.index') }}" aria-current="page" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-slate-900 px-3 text-[11px] font-extrabold text-white">Tukar Jadwal</a>
    </nav>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-950 md:text-2xl">Pertukaran Jadwal Kerja</h1>
            <p class="mt-1 text-xs text-slate-500">Ajukan atau kelola penukaran shift dengan rekan kerja secara aman.</p>
        </div>
        <a href="{{ route('employee.shift-swaps.create') }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-xs font-black text-white shadow-xs hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tukar Jadwal Baru
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6">
            <a href="{{ route('employee.shift-swaps.index', ['tab' => 'my_requests']) }}" class="border-b-2 py-3 text-xs font-black transition {{ $tab === 'my_requests' ? 'border-slate-900 text-slate-950' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Permintaan Saya
            </a>
            <a href="{{ route('employee.shift-swaps.index', ['tab' => 'incoming']) }}" class="border-b-2 py-3 text-xs font-black transition flex items-center gap-2 {{ $tab === 'incoming' ? 'border-slate-900 text-slate-950' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
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
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-slate-800">Belum ada permohonan</p>
                    <p class="mt-1 text-xs text-slate-500">Anda belum pernah mengajukan penukaran jadwal.</p>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($myRequests as $swap)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                            {{ $swap->status_label }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-400">Diajukan {{ $swap->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900">
                                        Tukar dengan <span class="text-indigo-600">{{ $swap->target?->full_name }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Tanggal: <strong class="font-bold text-slate-800">{{ $swap->requester_work_date->translatedFormat('d F Y') }}</strong>
                                        @if($swap->requester_work_date->ne($swap->target_work_date))
                                            &rarr; <strong class="font-bold text-slate-800">{{ $swap->target_work_date->translatedFormat('d F Y') }}</strong>
                                        @endif
                                    </p>
                                </div>
                                @if($swap->isPendingTarget() || $swap->isPendingAdmin())
                                    <form action="{{ route('employee.shift-swaps.cancel', $swap) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan tukar jadwal ini?')">
                                        @csrf
                                        <button class="flex min-h-[44px] w-full items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 text-xs font-extrabold text-rose-700 transition hover:bg-rose-100 sm:w-auto">
                                            Batalkan
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Comparison preview stacked cards -->
                            <div class="mt-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs sm:grid-cols-2">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-slate-400">Shift Saya Asli</span>
                                    <p class="font-extrabold text-slate-800">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-indigo-500">Shift Rekan Akan Diterima</span>
                                    <p class="font-extrabold text-indigo-900">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                                </div>
                            </div>

                            @if($swap->requester_reason)
                                <p class="mt-2 text-xs italic text-slate-500">Alasan saya: "{{ $swap->requester_reason }}"</p>
                            @endif
                            @if($swap->target_response_reason)
                                <p class="mt-1 text-xs italic text-slate-600">Catatan rekan: "{{ $swap->target_response_reason }}"</p>
                            @endif
                            @if($swap->admin_response_reason)
                                <p class="mt-1 text-xs italic text-slate-600">Catatan admin: "{{ $swap->admin_response_reason }}"</p>
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
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-slate-800">Tidak ada permohonan masuk</p>
                    <p class="mt-1 text-xs text-slate-500">Belum ada rekan yang mengajukan penukaran jadwal dengan Anda.</p>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($incomingRequests as $swap)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase {{ $swap->status_badge_class }}">
                                            {{ $swap->status_label }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-400">Diajukan {{ $swap->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900">
                                        Permintaan dari <span class="text-rose-600">{{ $swap->requester?->full_name }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Tanggal: <strong class="font-bold text-slate-800">{{ $swap->target_work_date->translatedFormat('d F Y') }}</strong>
                                    </p>
                                </div>

                                @if($swap->isPendingTarget())
                                    <div class="flex gap-2">
                                        <button type="button" @click="respondModal = true; swapId = {{ $swap->id }}; actionType = 'accept'; targetName = '{{ $swap->requester?->full_name }}'; reqDate = '{{ $swap->target_work_date->translatedFormat('d F Y') }}'" class="flex min-h-[44px] items-center justify-center gap-1 rounded-xl bg-emerald-600 px-4 text-xs font-black text-white hover:bg-emerald-700">
                                            Terima
                                        </button>
                                        <button type="button" @click="respondModal = true; swapId = {{ $swap->id }}; actionType = 'reject'; targetName = '{{ $swap->requester?->full_name }}'; reqDate = '{{ $swap->target_work_date->translatedFormat('d F Y') }}'" class="flex min-h-[44px] items-center justify-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-4 text-xs font-black text-rose-700 hover:bg-rose-100">
                                            Tolak
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Comparison preview stacked cards -->
                            <div class="mt-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs sm:grid-cols-2">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-slate-400">Shift Rekan ({{ $swap->requester?->full_name }})</span>
                                    <p class="font-extrabold text-slate-800">{{ $swap->requesterShift?->name }} ({{ substr($swap->requesterShift?->start_time, 0, 5) }}–{{ substr($swap->requesterShift?->end_time, 0, 5) }})</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] font-black uppercase text-indigo-500">Shift Saya Saat Ini</span>
                                    <p class="font-extrabold text-indigo-900">{{ $swap->targetShift?->name }} ({{ substr($swap->targetShift?->start_time, 0, 5) }}–{{ substr($swap->targetShift?->end_time, 0, 5) }})</p>
                                </div>
                            </div>

                            @if($swap->requester_reason)
                                <p class="mt-2 text-xs italic text-slate-500">Alasan rekan: "{{ $swap->requester_reason }}"</p>
                            @endif
                        </article>
                    @endforeach
                </div>
                <div class="mt-4">{{ $incomingRequests->links() }}</div>
            @endif
        </section>
    @endif

    <!-- Respond Modal -->
    <div x-show="respondModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div @click.away="respondModal = false" class="w-full max-w-md space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900" x-text="actionType === 'accept' ? 'Terima Permintaan Tukar Jadwal' : 'Tolak Permintaan Tukar Jadwal'"></h3>
                <button type="button" @click="respondModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <p class="text-xs text-slate-600">
                Konfirmasi <span class="font-extrabold" x-text="actionType === 'accept' ? 'menerima' : 'menolak'"></span> permintaan tukar jadwal dari <strong class="font-bold text-slate-900" x-text="targetName"></strong> untuk tanggal <span class="font-bold text-slate-800" x-text="reqDate"></span>.
            </p>
            <form :action="'{{ url('app/shift-swaps') }}/' + swapId + '/respond'" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" :value="actionType">
                <div>
                    <label for="target-reason" class="mb-1 block text-xs font-black text-slate-700">Alasan / Catatan Respon <span x-show="actionType === 'reject'" class="text-rose-600">*</span></label>
                    <textarea id="target-reason" name="reason" rows="3" :required="actionType === 'reject'" minlength="5" placeholder="Tuliskan catatan alasan..." class="w-full rounded-xl border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"></textarea>
                    <p x-show="actionType === 'reject'" class="mt-1 text-[10px] text-slate-500">Wajib diisi minimal 5 karakter jika menolak.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="respondModal = false" class="min-h-[44px] rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" :class="actionType === 'accept' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'" class="min-h-[44px] rounded-xl px-5 text-xs font-black text-white">
                        Kirim Respon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
