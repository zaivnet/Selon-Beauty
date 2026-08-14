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
@endphp

<div class="space-y-5">
    <nav class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xs" aria-label="Jenis laporan">
        <a href="{{ route('admin.reports.attendance') }}" class="flex min-h-[44px] shrink-0 items-center rounded-lg px-4 text-xs font-extrabold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">Laporan Kehadiran</a>
        <a href="{{ route('admin.monthly-recaps.index', $query) }}" aria-current="page" class="flex min-h-[44px] shrink-0 items-center rounded-lg bg-slate-900 px-4 text-xs font-extrabold text-white shadow-sm">Rekap Bulanan</a>
    </nav>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="border-b border-slate-100 p-5 md:flex md:items-start md:justify-between md:gap-6 md:p-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-rose-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700">Data kehadiran</span>
                    <span class="text-xs font-bold text-slate-400">{{ $period['label'] }}</span>
                </div>
                <h2 class="mt-3 text-xl font-black tracking-tight text-slate-950 md:text-2xl">Dasar rekap sebelum proses payroll</h2>
                <p class="mt-1.5 max-w-2xl text-xs font-medium leading-5 text-slate-500">Rekap ini berisi waktu kerja dan kehadiran, bukan slip atau perhitungan nominal gaji. Periksa semua data berstatus perlu review sebelum digunakan.</p>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 md:mt-0 md:shrink-0">
                <a href="{{ route('admin.monthly-recaps.export-summary', $query) }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-xs font-extrabold text-emerald-800 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">CSV Ringkasan</a>
                <a href="{{ route('admin.monthly-recaps.export-detail', $query) }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">CSV Detail</a>
            </div>
        </div>

        <div class="grid divide-y divide-slate-100 bg-slate-50/70 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Karyawan direkap</p><p class="mt-1 font-mono text-2xl font-black text-slate-950">{{ count($recapData['recaps']) }}</p></div>
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Ready</p><p class="mt-1 font-mono text-2xl font-black text-emerald-700">{{ $readyCount }}</p></div>
            <div class="p-4"><p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Perlu review</p><p class="mt-1 font-mono text-2xl font-black text-amber-700">{{ $reviewCount }}</p></div>
        </div>
        <p class="border-t border-slate-100 bg-white px-5 py-3 text-[10px] font-bold text-slate-500">Attendance Rate = hari hadir, termasuk terlambat ÷ hari kerja efektif × 100%.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs md:p-5">
        <form action="{{ route('admin.monthly-recaps.index') }}" method="GET" class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 xl:grid-cols-6">
            <div class="min-w-0">
                <label for="recap-month" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Bulan</label>
                <select id="recap-month" name="month" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    @foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected((int) $filters['month'] === $month)>{{ \Carbon\Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="recap-year" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Tahun</label>
                <select id="recap-year" name="year" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    @foreach(range(now()->year + 1, now()->year - 5) as $year)<option value="{{ $year }}" @selected((int) $filters['year'] === $year)>{{ $year }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                <label for="recap-employee" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Karyawan</label>
                <select id="recap-employee" name="employee_id" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua karyawan</option>
                    @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) ($filters['employee_id'] ?? 0) === $employee->id)>{{ $employee->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                <label for="recap-job-title" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Jabatan</label>
                <select id="recap-job-title" name="job_title_id" class="min-h-[44px] w-full rounded-xl border-slate-300 bg-white text-xs font-bold text-slate-800 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua jabatan</option>
                    @foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((int) ($filters['job_title_id'] ?? 0) === $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach
                </select>
            </div>
            <button class="min-h-[44px] self-end rounded-xl bg-rose-600 px-4 font-extrabold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">Terapkan Filter</button>
            <a href="{{ route('admin.monthly-recaps.index') }}" class="flex min-h-[44px] self-end items-center justify-center rounded-xl bg-slate-100 px-4 font-extrabold text-slate-700 transition hover:bg-slate-200">Reset</a>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-black text-slate-900">Ringkasan per karyawan</h3><p class="mt-0.5 text-[11px] text-slate-500">Klik nama untuk memeriksa rincian harian dan sumber masalah.</p></div>
        @if($recaps->isEmpty())
            <div class="px-5 py-14 text-center"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div><p class="mt-3 text-sm font-black text-slate-800">Belum ada data rekap</p><p class="mt-1 text-xs text-slate-500">Coba ubah periode atau filter karyawan.</p></div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1180px] text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-600"><tr><th class="sticky left-0 bg-slate-50 px-4 py-3">Karyawan</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Hari Kerja</th><th class="px-3 py-3">Hadir</th><th class="px-3 py-3">Terlambat</th><th class="px-3 py-3">Tidak Hadir</th><th class="px-3 py-3">I / S / C</th><th class="px-3 py-3">Libur</th><th class="px-3 py-3">Jam Kerja</th><th class="px-3 py-3">Lembur Credited</th><th class="px-3 py-3">Rate</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($recaps as $recap) @php($s = $recap['summary'])
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="sticky left-0 bg-white px-4 py-3"><a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="font-extrabold text-slate-900 hover:text-rose-700">{{ $s['employee_name'] }}</a><span class="mt-0.5 block text-[10px] text-slate-500">{{ $s['employee_code'] }} · {{ $s['job_title'] ?: 'Karyawan' }}</span></td>
                            <td class="px-3 py-3"><span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black {{ $s['readiness_status'] === 'READY' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">{{ $s['readiness_label'] }}</span></td>
                            <td class="px-3 py-3 font-mono font-bold">{{ $s['effective_work_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-emerald-700">{{ $s['present_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-amber-700">{{ $s['late_days'] }}</td><td class="px-3 py-3 font-mono font-bold text-rose-700">{{ $s['absent_days'] }}</td><td class="px-3 py-3 font-mono font-bold">{{ $s['permission_days'] }} / {{ $s['sick_days'] }} / {{ $s['leave_days'] }}</td><td class="px-3 py-3 font-mono font-bold">{{ $s['holiday_days'] + $s['off_days'] }}</td><td class="px-3 py-3 font-mono font-bold">{{ $duration($s['regular_worked_minutes']) }}</td><td class="px-3 py-3 font-mono font-bold text-indigo-700">{{ $duration($s['overtime_credited_minutes']) }}</td><td class="px-3 py-3 font-mono font-black">{{ number_format($s['attendance_rate'], 1) }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($recaps as $recap) @php($s = $recap['summary'])
                    <article class="p-4">
                        <div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="block truncate text-sm font-black text-slate-900">{{ $s['employee_name'] }}</a><p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $s['employee_code'] }} · {{ $s['job_title'] ?: 'Karyawan' }}</p></div><span class="shrink-0 rounded-lg border px-2 py-1 text-[9px] font-black {{ $s['readiness_status'] === 'READY' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">{{ $s['readiness_label'] }}</span></div>
                        <dl class="mt-3 grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 text-center"><div class="bg-white p-2"><dt class="text-[9px] font-bold text-slate-500">Hari kerja</dt><dd class="mt-0.5 font-mono text-sm font-black">{{ $s['effective_work_days'] }}</dd></div><div class="bg-white p-2"><dt class="text-[9px] font-bold text-slate-500">Hadir</dt><dd class="mt-0.5 font-mono text-sm font-black text-emerald-700">{{ $s['present_days'] }}</dd></div><div class="bg-white p-2"><dt class="text-[9px] font-bold text-slate-500">Rate</dt><dd class="mt-0.5 font-mono text-sm font-black">{{ number_format($s['attendance_rate'], 1) }}%</dd></div></dl>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[10px] font-bold text-slate-600"><span>Terlambat <b class="font-mono text-amber-700">{{ $s['late_days'] }}</b></span><span>Tidak hadir <b class="font-mono text-rose-700">{{ $s['absent_days'] }}</b></span><span>Jam kerja <b class="font-mono text-slate-900">{{ $duration($s['regular_worked_minutes']) }}</b></span><span>Lembur <b class="font-mono text-indigo-700">{{ $duration($s['overtime_credited_minutes']) }}</b></span></div>
                        <a href="{{ route('admin.monthly-recaps.show', ['employee' => $s['employee_id'], ...$detailQuery]) }}" class="mt-3 flex min-h-[44px] items-center justify-center rounded-xl bg-slate-100 text-xs font-extrabold text-slate-700">Lihat rincian</a>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 bg-slate-50 p-4">{{ $recaps->links() }}</div>
        @endif
    </section>
</div>
@endsection
