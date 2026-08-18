@extends('layouts.admin')

@section('title', 'Pusat Perhatian')
@section('page-title', 'Pusat Perhatian')

@section('content')
@php
    $summaryCards = [
        ['label' => 'Critical', 'value' => $exceptions['summary']['critical'], 'classes' => 'border-rose-200 dark:border-rose-900/60 bg-rose-50/70 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300', 'dot' => 'bg-rose-600'],
        ['label' => 'Warning', 'value' => $exceptions['summary']['warning'], 'classes' => 'border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/40 text-amber-900 dark:text-amber-300', 'dot' => 'bg-amber-500'],
        ['label' => 'Pending Approval', 'value' => $exceptions['summary']['pending_approval'], 'classes' => 'border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300', 'dot' => 'bg-indigo-600'],
        ['label' => 'Active Overtime', 'value' => $exceptions['summary']['active_overtime'], 'classes' => 'border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300', 'dot' => 'bg-emerald-600'],
    ];
    $hasRefinement = ! $exceptions['is_today'] || filled($filters['severity']) || filled($filters['category']) || filled($filters['employee_id']) || filled($filters['job_title_id']);
    $severityStyles = [
        'critical' => ['rail' => 'border-l-rose-600', 'badge' => 'border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300'],
        'warning' => ['rail' => 'border-l-amber-500', 'badge' => 'border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300'],
        'info' => ['rail' => 'border-l-indigo-500', 'badge' => 'border-indigo-200 dark:border-indigo-900/60 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300'],
    ];
@endphp

<div class="space-y-5 md:space-y-6">
    <header class="flex flex-col gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs sm:flex-row sm:items-start sm:justify-between md:p-6 transition-colors">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-rose-700 dark:text-rose-400">Monitoring & navigasi</p>
            <h1 class="mt-1 text-xl font-black tracking-tight text-slate-950 dark:text-slate-100 md:text-2xl">Pusat Perhatian</h1>
            <p class="mt-2 max-w-2xl text-xs font-medium leading-relaxed text-slate-500 dark:text-slate-400">Kondisi ini diturunkan dari status operasional saat ini. Halaman ini tidak mengubah, menyetujui, atau menutup data apa pun.</p>
        </div>
        <div class="shrink-0 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 px-4 py-3 text-left sm:text-right">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Hasil pada {{ $exceptions['date_label'] }}</p>
            <p class="mt-1 font-mono text-xl font-black text-slate-950 dark:text-slate-100">{{ $exceptions['summary']['total'] }}</p>
            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">Dibuat {{ $exceptions['generated_at']->format('H:i') }}</p>
        </div>
    </header>

    <section aria-label="Ringkasan exception" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="min-w-0 rounded-2xl border p-4 shadow-xs {{ $card['classes'] }}">
                <div class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full {{ $card['dot'] }}" aria-hidden="true"></span><p class="truncate text-[9px] font-black uppercase tracking-[0.14em]">{{ $card['label'] }}</p></div>
                <p class="mt-2 font-mono text-2xl font-black leading-none text-slate-950 dark:text-slate-100">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="filter-title" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs md:p-5 transition-colors">
        <div class="mb-4 flex items-center justify-between gap-3"><div><h2 id="filter-title" class="text-sm font-black text-slate-900 dark:text-slate-100">Saring perhatian</h2><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Tanggal memakai zona waktu aplikasi.</p></div>@if($hasRefinement)<span class="rounded-md border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/60 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-rose-700 dark:text-rose-400">Filter aktif</span>@endif</div>
        <form method="GET" action="{{ route('admin.operational-exceptions.index') }}" class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter operational exceptions">
            <div class="min-w-0"><label for="date" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">Tanggal</label><x-date-input name="date" :value="$filters['date']" :max="now(config('app.timezone'))->toDateString()" required wrapper-class="bg-white dark:bg-slate-950" /></div>
            <div class="min-w-0"><label for="severity" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">Severity</label><select id="severity" name="severity" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 ui-input ui-select"><option value="">Semua severity</option><option value="critical" @selected($filters['severity'] === 'critical')>Critical</option><option value="warning" @selected($filters['severity'] === 'warning')>Warning</option><option value="info" @selected($filters['severity'] === 'info')>Info</option></select></div>
            <div class="min-w-0"><label for="category" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">Kategori</label><select id="category" name="category" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 ui-input ui-select"><option value="">Semua kategori</option>@foreach($categories as $key => $category)<option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $category['label'] }}</option>@endforeach</select></div>
            <div class="min-w-0"><label for="employee_id" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">Karyawan</label><select id="employee_id" name="employee_id" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 ui-input ui-select"><option value="">Semua karyawan</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>{{ $employee->full_name }}</option>@endforeach</select></div>
            <div class="min-w-0"><label for="job_title_id" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">Jabatan</label><select id="job_title_id" name="job_title_id" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 text-xs font-bold text-slate-800 dark:text-slate-100 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 ui-input ui-select"><option value="">Semua jabatan</option>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((string) $filters['job_title_id'] === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-5 lg:ml-auto lg:w-72"><a href="{{ route('admin.operational-exceptions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 text-xs font-extrabold text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20">Reset</a><button type="submit" class="min-h-11 rounded-xl bg-rose-600 px-4 text-xs font-extrabold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/40 focus:ring-offset-2 ui-btn ui-btn-primary">Terapkan</button></div>
        </form>
        @error('date')<p class="mt-2 text-xs font-bold text-rose-700 dark:text-rose-400">{{ $message }}</p>@enderror
    </section>

    <section aria-labelledby="priority-list-title" class="space-y-3">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-rose-700 dark:text-rose-400">Urutan prioritas</p><h2 id="priority-list-title" class="mt-1 text-base font-black text-slate-950 dark:text-slate-100">Daftar kondisi</h2></div>@if($items->total() > 0)<p class="font-mono text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $items->firstItem() }}–{{ $items->lastItem() }} / {{ $items->total() }}</p>@endif</div>

        @if($items->isEmpty())
            <div class="lg:hidden">@include('admin.operational_exceptions._empty', ['hasRefinement' => $hasRefinement])</div>
            <div class="hidden lg:block">@include('admin.operational_exceptions._empty', ['hasRefinement' => $hasRefinement])</div>
        @else
            <div class="space-y-3 lg:hidden">@foreach($items as $item) @include('admin.operational_exceptions._item', ['item' => $item, 'variant' => 'mobile']) @endforeach</div>
            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs lg:block">
                <table class="w-full table-fixed text-left ui-table"><thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80"><tr class="text-[9px] font-black uppercase tracking-[0.13em] text-slate-500 dark:text-slate-400"><th class="w-[13%] px-4 py-3">Severity</th><th class="w-[30%] px-4 py-3">Kondisi</th><th class="w-[19%] px-4 py-3">Karyawan</th><th class="w-[25%] px-4 py-3">Fakta Kunci</th><th class="w-[13%] px-4 py-3 text-right">Tujuan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($items as $item)
                            @php
                                $style = $severityStyles[$item['severity']] ?? $severityStyles['info'];
                            @endphp
                            <tr class="border-l-4 align-top transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50 {{ $style['rail'] }}">
                                <td class="px-4 py-4"><span class="inline-flex rounded-md border px-2 py-1 text-[9px] font-black uppercase tracking-[0.12em] {{ $style['badge'] }}">{{ $item['severity'] }}</span></td>
                                <td class="min-w-0 px-4 py-4"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ mb_strtoupper($item['category_label']) }}</p><p class="mt-1 break-words text-xs font-extrabold leading-relaxed text-slate-900 dark:text-slate-100">{{ $item['message'] }}</p></td>
                                <td class="min-w-0 px-4 py-4">@if($item['employee_name'])<p class="break-words text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $item['employee_name'] }}</p><p class="mt-1 break-words text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $item['employee_code'] }}@if($item['job_title']) · {{ $item['job_title'] }}@endif</p>@else<span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Sistem</span>@endif</td>
                                <td class="min-w-0 px-4 py-4">@include('admin.operational_exceptions._facts', ['item' => $item])</td>
                                <td class="px-4 py-4 text-right">@if($item['action_url'] && $item['action_label'])<a href="{!! $item['action_url'] !!}" class="inline-flex min-h-11 items-center justify-end text-xs font-extrabold text-rose-700 dark:text-rose-400 hover:text-rose-900 dark:hover:text-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-500/30">{{ $item['action_label'] }} →</a>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())<div class="pt-2">{{ $items->withQueryString()->links() }}</div>@endif
        @endif
    </section>
</div>
@endsection
