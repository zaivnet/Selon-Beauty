@extends('layouts.admin')

@section('title', 'Rekap Kehadiran Bulanan')
@section('page-title', 'Rekap Kehadiran Bulanan')

@section('content')
@php
    $period = $recapData['period'];
    $readyCount = collect($recapData['recaps'])->where('summary.readiness_status', 'READY')->count();
    $reviewCount = collect($recapData['recaps'])->where('summary.readiness_status', 'NEEDS_REVIEW')->count();
    $duration = static function (int $minutes): string {
        if ($minutes <= 0) return '0m';
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;
        return trim(($hours ? $hours.'j ' : '').($remainder ? $remainder.'m' : ''));
    };
    $query = array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    $returnContext = array_filter([
        'return_employee_id' => $filters['employee_id'] ?? null,
        'return_job_title_id' => $filters['job_title_id'] ?? null,
        'return_page' => $recaps->currentPage() > 1 ? $recaps->currentPage() : null,
    ]);
    $detailQuery = ['year' => $filters['year'], 'month' => $filters['month'], ...$returnContext];
    $isManager = in_array(auth()->user()->role, ['superadmin', 'owner'], true);
    $isClosed = $attendancePeriod->isClosed();
@endphp

<div class="space-y-5" x-data="{ showCloseModal: false, showReopenModal: false }">
    <nav class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xs transition-colors" aria-label="Jenis laporan">
        <a href="{{ route('admin.reports.attendance') }}" class="flex min-h-[44px] shrink-0 items-center rounded-lg px-4 text-xs font-extrabold text-slate-500 dark:text-slate-400 transition hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200">Laporan Kehadiran</a>
        <a href="{{ route('admin.monthly-recaps.index', $query) }}" aria-current="page" class="flex min-h-[44px] shrink-0 items-center rounded-lg bg-slate-900 dark:bg-rose-600 px-4 text-xs font-extrabold text-white shadow-sm">Rekap Bulanan</a>
    </nav>

    <!-- Period Closing Status Card -->
    <section class="overflow-hidden rounded-2xl border {{ $isClosed ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/40 dark:bg-indigo-950/40' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900' }} p-5 shadow-xs md:p-6 transition-colors">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if($isClosed)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600 px-3 py-1 text-xs font-black uppercase tracking-wider text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            PERIODE TERKUNCI (FINAL)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-3 py-1 text-xs font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            PERIODE TERBUKA
                        </span>
                    @endif
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $period['label'] }}</span>
                </div>

                @if($isClosed)
                    <p class="mt-2 text-xs font-medium leading-relaxed text-slate-700 dark:text-slate-300">
                        Periode ditutup oleh <strong class="font-bold text-slate-900 dark:text-slate-100">{{ $attendancePeriod->closedBy?->name ?? 'Sistem' }}</strong> pada <span class="font-mono text-slate-800 dark:text-slate-200">{{ $attendancePeriod->closed_at?->format('d M Y H:i') }} WIB</span>.
                    </p>
                    @if($attendancePeriod->close_reason)
                        <p class="text-xs italic text-slate-600 dark:text-slate-400">Alasan: "{{ $attendancePeriod->close_reason }}"</p>
                    @endif
                @else
                    <p class="mt-2 text-xs font-medium leading-relaxed text-slate-600 dark:text-slate-400">
                        Data kehadiran masih dapat disesuaikan. Penutupan periode akan mengunci seluruh perubahan absensi, lembur, dan jadwal pada bulan ini.
                    </p>
                @endif
            </div>

            @if($isManager)
                <div class="shrink-0">
                    @if($isClosed)
                        <button type="button" @click="showReopenModal = true" class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-slate-800 px-5 text-xs font-black text-indigo-700 dark:text-indigo-300 shadow-xs transition hover:bg-indigo-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            Buka Kembali Periode
                        </button>
                    @else
                        @if($closeEligibility['is_eligible'])
                            <button type="button" @click="showCloseModal = true" class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-slate-900 dark:bg-slate-700 px-5 text-xs font-black text-white shadow-xs transition hover:bg-slate-800 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-900 sm:w-auto">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Tutup Periode
                            </button>
                        @else
                            <button type="button" disabled title="Selesaikan masalah operasional terlebih dahulu" class="flex min-h-[44px] w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-200 dark:bg-slate-800 px-5 text-xs font-black text-slate-400 dark:text-slate-500 opacity-80 sm:w-auto">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Tutup Periode (Belum Siap)
                            </button>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        @if(! $isClosed && ! $closeEligibility['is_eligible'])
            <div class="mt-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/40 p-3.5 text-xs text-amber-900 dark:text-amber-300">
                <div class="flex items-start gap-2.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-700 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="flex-1 min-w-0">
                        <p class="font-extrabold text-amber-900 dark:text-amber-200">Periode belum dapat ditutup karena masalah berikut:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4 text-amber-800 dark:text-amber-300 font-medium">
                            @foreach($closeEligibility['issues'] as $issue)
                                <li>{{ ucfirst($issue) }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('admin.operational-exceptions.index') }}" class="mt-2 inline-flex items-center gap-1 font-bold text-amber-900 dark:text-amber-200 underline hover:text-amber-950 dark:hover:text-amber-100">
                            Buka Pusat Perhatian / Operational Exceptions &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <!-- Close Modal -->
    <div x-show="showCloseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="showCloseModal = false" class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100">Tutup Periode Kehadiran</h3>
                <button type="button" @click="showCloseModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">&times;</button>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Penutupan periode {{ $period['label'] }} akan mengunci seluruh data presensi, lembur, dan jadwal bulan tersebut dari perubahan biasa.
            </p>
            <form action="{{ route('admin.monthly-recaps.close') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="year" value="{{ $filters['year'] }}">
                <input type="hidden" name="month" value="{{ $filters['month'] }}">
                <div>
                    <label for="close-reason" class="block text-xs font-black text-slate-700 dark:text-slate-300 mb-1">Alasan Penutupan Periode <span class="text-rose-600 dark:text-rose-400">*</span></label>
                    <textarea id="close-reason" name="reason" rows="3" required minlength="5" placeholder="Contoh: Rekap Agustus sudah diverifikasi untuk payroll." class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:border-slate-900 focus:ring-slate-900"></textarea>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Minimal 5 karakter wajib diisi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCloseModal = false" class="min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-xl bg-slate-900 dark:bg-slate-700 px-5 text-xs font-black text-white hover:bg-slate-800 dark:hover:bg-slate-600">Ya, Tutup Periode</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reopen Modal -->
    <div x-show="showReopenModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="showReopenModal = false" class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100">Buka Kembali Periode Kehadiran</h3>
                <button type="button" @click="showReopenModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">&times;</button>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Membuka kembali periode {{ $period['label'] }} akan memperbolehkan perubahan absensi dan koreksi pada bulan tersebut kembali.
            </p>
            <form action="{{ route('admin.monthly-recaps.reopen') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="year" value="{{ $filters['year'] }}">
                <input type="hidden" name="month" value="{{ $filters['month'] }}">
                <div>
                    <label for="reopen-reason" class="block text-xs font-black text-slate-700 dark:text-slate-300 mb-1">Alasan Pembukaan Kembali <span class="text-rose-600 dark:text-rose-400">*</span></label>
                    <textarea id="reopen-reason" name="reason" rows="3" required minlength="5" placeholder="Contoh: Ditemukan koreksi absensi yang valid." class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:border-indigo-600 focus:ring-indigo-600"></textarea>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Minimal 5 karakter wajib diisi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showReopenModal = false" class="min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-xl bg-indigo-600 px-5 text-xs font-black text-white hover:bg-indigo-700">Ya, Buka Kembali</button>
                </div>
            </form>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="border-b border-slate-100 dark:border-slate-800 p-5 md:flex md:items-start md:justify-between md:gap-6 md:p-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-rose-50 dark:bg-rose-950/60 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">Data kehadiran</span>
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500">{{ $period['label'] }}</span>
                </div>
                <h2 class="mt-3 text-xl font-black tracking-tight text-slate-950 dark:text-slate-100 md:text-2xl">Dasar rekap sebelum proses payroll</h2>
                <p class="mt-1.5 max-w-2xl text-xs font-medium leading-5 text-slate-500 dark:text-slate-400">Rekap ini berisi waktu kerja dan kehadiran, bukan slip atau perhitungan nominal gaji. Periksa semua data berstatus perlu review sebelum digunakan.</p>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 md:mt-0 md:shrink-0">
                <a href="{{ route('admin.monthly-recaps.export-summary', $query) }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 px-4 text-xs font-extrabold text-emerald-800 dark:text-emerald-300 transition hover:bg-emerald-100 dark:hover:bg-emerald-900/40 focus:outline-none focus:ring-2 focus:ring-emerald-500">CSV Ringkasan</a>
                <a href="{{ route('admin.monthly-recaps.export-detail', $query) }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">CSV Detail</a>
            </div>
        </div>

        <div class="grid divide-y divide-slate-100 dark:divide-slate-800 bg-slate-50/70 dark:bg-slate-800/50 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Karyawan direkap</p><p class="mt-1 font-mono text-2xl font-black text-slate-950 dark:text-slate-100">{{ count($recapData['recaps']) }}</p></div>
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Ready</p><p class="mt-1 font-mono text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ $readyCount }}</p></div>
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">Perlu review</p><p class="mt-1 font-mono text-2xl font-black text-amber-700 dark:text-amber-400">{{ $reviewCount }}</p></div>
        </div>
        <p class="border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 px-5 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-400">Attendance Rate = hari hadir, termasuk terlambat ÷ hari kerja efektif × 100%.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs md:p-5 transition-colors">
        <form action="{{ route('admin.monthly-recaps.index') }}" method="GET" class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 xl:grid-cols-6">
            <div class="min-w-0">
                <label for="recap-month" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Bulan</label>
                <select id="recap-month" name="month" class="min-h-[44px] w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    @foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected((int) $filters['month'] === $month)>{{ \Carbon\Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="recap-year" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Tahun</label>
                <select id="recap-year" name="year" class="min-h-[44px] w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    @foreach(range(now()->year + 1, now()->year - 5) as $year)<option value="{{ $year }}" @selected((int) $filters['year'] === $year)>{{ $year }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                <label for="recap-employee" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Karyawan</label>
                <select id="recap-employee" name="employee_id" class="min-h-[44px] w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua karyawan</option>
                    @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) ($filters['employee_id'] ?? 0) === $employee->id)>{{ $employee->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                <label for="recap-job-title" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Jabatan</label>
                <select id="recap-job-title" name="job_title_id" class="min-h-[44px] w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua jabatan</option>
                    @foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((int) ($filters['job_title_id'] ?? 0) === $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach
                </select>
            </div>
            <button class="min-h-[44px] self-end rounded-xl bg-rose-600 px-4 font-extrabold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">Terapkan Filter</button>
            <a href="{{ route('admin.monthly-recaps.index') }}" class="flex min-h-[44px] self-end items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 px-4 font-extrabold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200 dark:hover:bg-slate-700">Reset</a>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="border-b border-slate-100 dark:border-slate-800 px-5 py-4"><h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Ringkasan per karyawan</h3><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Klik nama untuk memeriksa rincian harian dan sumber masalah.</p></div>
        @if($recaps->isEmpty())
            <div class="px-5 py-14 text-center"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div><p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-200">Belum ada data rekap</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Coba ubah periode atau filter karyawan.</p></div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1180px] text-left text-xs border-collapse">
                    <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400"><tr><th class="sticky left-0 bg-slate-50 dark:bg-slate-800/90 px-4 py-3">Karyawan</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Hari Kerja</th><th class="px-3 py-3">Hadir</th><th class="px-3 py-3">Terlambat</th><th class="px-3 py-3">Tidak Hadir</th><th class="px-3 py-3">I / S / C</th><th class="px-3 py-3">Libur</th><th class="px-3 py-3">Jam Kerja</th><th class="px-3 py-3">Lembur Credited</th><th class="px-3 py-3">Rate</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($recaps as $recap) @php($s = $recap['summary'])
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <td class="sticky left-0 bg-white dark:bg-slate-900 px-4 py-3"><a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="font-extrabold text-slate-900 dark:text-slate-100 hover:text-rose-700 dark:hover:text-rose-400">{{ $s['employee_name'] }}</a><span class="mt-0.5 block text-[10px] text-slate-500 dark:text-slate-400">{{ $s['employee_code'] }} · {{ $s['job_title'] ?: 'Karyawan' }}</span></td>
                            <td class="px-3 py-3"><span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black {{ $s['readiness_status'] === 'READY' ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300' }}">{{ $s['readiness_label'] }}</span></td>
                            <td class="px-3 py-3 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $s['effective_work_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $s['present_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-amber-700 dark:text-amber-400">{{ $s['late_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-rose-700 dark:text-rose-400">{{ $s['absent_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $s['permission_days'] }} / {{ $s['sick_days'] }} / {{ $s['leave_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $s['holiday_days'] + $s['off_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $duration($s['regular_worked_minutes']) }}</td><td class="px-3 py-3 font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ $duration($s['overtime_credited_minutes']) }}</td><td class="px-3 py-3 font-mono font-black text-slate-900 dark:text-slate-100">{{ number_format($s['attendance_rate'], 1) }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach($recaps as $recap) @php($s = $recap['summary'])
                    <article class="p-4">
                        <div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="block truncate text-sm font-black text-slate-900 dark:text-slate-100">{{ $s['employee_name'] }}</a><p class="mt-0.5 truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $s['employee_code'] }} · {{ $s['job_title'] ?: 'Karyawan' }}</p></div><span class="shrink-0 rounded-lg border px-2 py-1 text-[9px] font-black {{ $s['readiness_status'] === 'READY' ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300' }}">{{ $s['readiness_label'] }}</span></div>
                        <dl class="mt-3 grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-200 dark:bg-slate-800 text-center"><div class="bg-white dark:bg-slate-900 p-2"><dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Hari kerja</dt><dd class="mt-0.5 font-mono text-sm font-black text-slate-900 dark:text-slate-100">{{ $s['effective_work_days'] }}</dd></div><div class="bg-white dark:bg-slate-900 p-2"><dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Hadir</dt><dd class="mt-0.5 font-mono text-sm font-black text-emerald-700 dark:text-emerald-400">{{ $s['present_days'] }}</dd></div><div class="bg-white dark:bg-slate-900 p-2"><dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Rate</dt><dd class="mt-0.5 font-mono text-sm font-black text-slate-900 dark:text-slate-100">{{ number_format($s['attendance_rate'], 1) }}%</dd></div></dl>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[10px] font-bold text-slate-600 dark:text-slate-400"><span>Terlambat <b class="font-mono text-amber-700 dark:text-amber-400">{{ $s['late_days'] }}</b></span><span>Tidak hadir <b class="font-mono text-rose-700 dark:text-rose-400">{{ $s['absent_days'] }}</b></span><span>Jam kerja <b class="font-mono text-slate-900 dark:text-slate-100">{{ $duration($s['regular_worked_minutes']) }}</b></span><span>Lembur <b class="font-mono text-indigo-700 dark:text-indigo-400">{{ $duration($s['overtime_credited_minutes']) }}</b></span></div>
                        <a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="mt-3 flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-extrabold text-slate-700 dark:text-slate-300">Lihat rincian</a>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-4">{{ $recaps->links() }}</div>
        @endif
    </section>
</div>
@endsection
