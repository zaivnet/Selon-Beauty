@extends('layouts.employee')

@section('title', 'Pengajuan Tukar Jadwal')

@section('content')
<div class="space-y-5">
    <!-- Navigation Tabs -->
    <div class="flex bg-slate-200/80 p-1 rounded-xl gap-1 text-xs font-bold">
        <a href="{{ route('employee.leave-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.leave-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Izin / Sakit / Cuti
        </a>
        <a href="{{ route('employee.overtime-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.overtime-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Lembur
        </a>
        @if(auth()->user()?->role !== 'superadmin' && auth()->user()?->employee?->attendance_enabled !== false)
            @php($pendingSwapCount = \App\Models\ShiftSwapRequest::where('target_employee_id', auth()->user()?->employee_id)->where('status', 'pending_target')->count())
            <a href="{{ route('employee.shift-swaps.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all flex items-center justify-center gap-1 {{ request()->routeIs('employee.shift-swaps.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Tukar Jadwal</span>
                @if($pendingSwapCount > 0)
                    <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[9px] font-black text-white leading-none">{{ $pendingSwapCount }}</span>
                @endif
            </a>
        @endif
    </div>

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-950 md:text-2xl">Pengajuan Tukar Jadwal Baru</h1>
            <p class="mt-1 text-xs text-slate-500">Pilih tanggal dan rekan kerja untuk melihat pratinjau sebelum mengajukan.</p>
        </div>
        <a href="{{ route('employee.shift-swaps.index') }}" class="flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 px-4 text-xs font-extrabold text-slate-700 hover:bg-slate-200">
            Kembali
        </a>
    </div>

    <!-- Step 1: Form Selector -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <form action="{{ route('employee.shift-swaps.create') }}" method="GET" class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
            <div>
                <label for="work-date" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Tanggal Jadwal Saya <span class="text-rose-600">*</span></label>
                <input type="date" id="work-date" name="work_date" value="{{ $reqDate }}" min="{{ now()->toDateString() }}" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-slate-900 focus:ring-slate-900" onchange="this.form.submit()">
            </div>
            <div>
                <label for="target-employee" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Pilih Rekan Kerja <span class="text-rose-600">*</span></label>
                <select id="target-employee" name="target_employee_id" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-slate-900 focus:ring-slate-900" onchange="this.form.submit()">
                    <option value="">-- Pilih Rekan Kerja --</option>
                    @foreach($eligibleTargets as $targetOption)
                        <option value="{{ $targetOption->id }}" @selected($targetId === $targetOption->id)>
                            {{ $targetOption->full_name }} ({{ $targetOption->jobTitle?->name ?? 'Karyawan' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    <!-- Step 2: Live Effective Schedule Preview -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
        <h2 class="text-sm font-black text-slate-900">Pratinjau Jadwal Efektif</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Requester Schedule -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Jadwal Saya ({{ $employee->full_name }})</span>
                    <span class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($reqDate)->translatedFormat('d F Y') }}</span>
                </div>
                @if($reqEffective['is_working_day'] && $reqEffective['shift'])
                    <div class="mt-2">
                        <span class="inline-flex rounded-lg bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-800">{{ $reqEffective['label'] }}</span>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $reqEffective['shift']->name }}</p>
                        <p class="text-xs font-mono font-bold text-slate-600">{{ substr($reqEffective['shift']->start_time, 0, 5) }} – {{ substr($reqEffective['shift']->end_time, 0, 5) }}</p>
                    </div>
                @else
                    <div class="mt-2 rounded-lg bg-rose-50 p-3 text-xs font-bold text-rose-800 border border-rose-200">
                        {{ $reqEffective['label'] }} — Tanggal ini bukan shift kerja aktif (WORK) sehingga tidak dapat ditukar.
                    </div>
                @endif
            </div>

            <!-- Target Schedule -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600">Jadwal Rekan ({{ $selectedTarget?->full_name ?? 'Belum Dipilih' }})</span>
                    <span class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($targetDate)->translatedFormat('d F Y') }}</span>
                </div>
                @if($selectedTarget)
                    @if($targetEffective && $targetEffective['is_working_day'] && $targetEffective['shift'])
                        <div class="mt-2">
                            <span class="inline-flex rounded-lg bg-indigo-100 px-2.5 py-1 text-[10px] font-black uppercase text-indigo-800">{{ $targetEffective['label'] }}</span>
                            <p class="mt-2 text-base font-black text-slate-900">{{ $targetEffective['shift']->name }}</p>
                            <p class="text-xs font-mono font-bold text-slate-600">{{ substr($targetEffective['shift']->start_time, 0, 5) }} – {{ substr($targetEffective['shift']->end_time, 0, 5) }}</p>
                        </div>
                    @else
                        <div class="mt-2 rounded-lg bg-rose-50 p-3 text-xs font-bold text-rose-800 border border-rose-200">
                            {{ $targetEffective['label'] ?? 'Tidak Valid' }} — Jadwal rekan pada tanggal ini bukan shift kerja aktif (WORK).
                        </div>
                    @endif
                @else
                    <p class="mt-4 text-xs italic text-slate-400">Silakan pilih rekan kerja terlebih dahulu untuk melihat jadwal.</p>
                @endif
            </div>
        </div>

        <!-- After Swap Preview Direction -->
        @if($reqEffective['is_working_day'] && $reqEffective['shift'] && $targetEffective && $targetEffective['is_working_day'] && $targetEffective['shift'])
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-2">
                <h3 class="text-xs font-black text-emerald-950 uppercase tracking-wider">Hasil Setelah Ditukar (Pratinjau)</h3>
                <div class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-800">Saya ({{ $employee->full_name }}) Akan Bekerja:</span>
                        <p class="font-black text-emerald-950">{{ $targetEffective['shift']->name }} ({{ substr($targetEffective['shift']->start_time, 0, 5) }}–{{ substr($targetEffective['shift']->end_time, 0, 5) }})</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-emerald-800">Rekan ({{ $selectedTarget->full_name }}) Akan Bekerja:</span>
                        <p class="font-black text-emerald-950">{{ $reqEffective['shift']->name }} ({{ substr($reqEffective['shift']->start_time, 0, 5) }}–{{ substr($reqEffective['shift']->end_time, 0, 5) }})</p>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <!-- Step 3: Submission Form -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <form action="{{ route('employee.shift-swaps.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="requester_work_date" value="{{ $reqDate }}">
            <input type="hidden" name="target_work_date" value="{{ $targetDate }}">
            <input type="hidden" name="target_employee_id" value="{{ $targetId }}">

            <div>
                <label for="reason" class="block text-xs font-black text-slate-700 mb-1.5">Alasan Penukaran Jadwal <span class="text-rose-600">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="5" placeholder="Contoh: Ada keperluan keluarga mendadak di jam pagi." class="w-full rounded-xl border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900">{{ old('reason') }}</textarea>
                <p class="mt-1 text-[10px] text-slate-500">Minimal 5 karakter wajib diisi.</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('employee.shift-swaps.index') }}" class="flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 px-5 text-xs font-bold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" @disabled(!($reqEffective['is_working_day'] && $reqEffective['shift'] && $targetEffective && $targetEffective['is_working_day'] && $targetEffective['shift'])) class="flex min-h-[44px] items-center justify-center rounded-xl bg-slate-900 px-6 text-xs font-black text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Kirim Permintaan Tukar Jadwal
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
