@extends('layouts.admin')

@section('title', 'Pusat Perhatian')
@section('page-title', 'Pusat Perhatian')

@section('content')
@php
    $summaryCards = [
        ['label' => 'Critical', 'value' => $exceptions['summary']['critical'], 'classes' => 'border-rose-200 bg-rose-50/70 text-rose-800', 'dot' => 'bg-rose-600'],
        ['label' => 'Warning', 'value' => $exceptions['summary']['warning'], 'classes' => 'border-amber-200 bg-amber-50/70 text-amber-900', 'dot' => 'bg-amber-500'],
        ['label' => 'Pending Approval', 'value' => $exceptions['summary']['pending_approval'], 'classes' => 'border-indigo-200 bg-indigo-50/70 text-indigo-800', 'dot' => 'bg-indigo-600'],
        ['label' => 'Active Overtime', 'value' => $exceptions['summary']['active_overtime'], 'classes' => 'border-emerald-200 bg-emerald-50/70 text-emerald-800', 'dot' => 'bg-emerald-600'],
    ];
    $hasRefinement = ! $exceptions['is_today'] || filled($filters['severity']) || filled($filters['category']) || filled($filters['employee_id']) || filled($filters['job_title_id']);
    $severityStyles = [
        'critical' => ['rail' => 'border-l-rose-600', 'badge' => 'border-rose-200 bg-rose-50 text-rose-800'],
        'warning' => ['rail' => 'border-l-amber-500', 'badge' => 'border-amber-200 bg-amber-50 text-amber-900'],
        'info' => ['rail' => 'border-l-indigo-500', 'badge' => 'border-indigo-200 bg-indigo-50 text-indigo-800'],
    ];
@endphp

<div class="space-y-5 md:space-y-6">
    <header class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:flex-row sm:items-start sm:justify-between md:p-6">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-rose-700">Monitoring & navigasi</p>
            <h1 class="mt-1 text-xl font-black tracking-tight text-slate-950 md:text-2xl">Pusat Perhatian</h1>
            <p class="mt-2 max-w-2xl text-xs font-medium leading-relaxed text-slate-500">Kondisi ini diturunkan dari status operasional saat ini. Halaman ini tidak mengubah, menyetujui, atau menutup data apa pun.</p>
        </div>
        <div class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left sm:text-right">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Hasil pada {{ $exceptions['date_label'] }}</p>
            <p class="mt-1 font-mono text-xl font-black text-slate-950">{{ $exceptions['summary']['total'] }}</p>
            <p class="text-[10px] font-semibold text-slate-500">Dibuat {{ $exceptions['generated_at']->format('H:i') }}</p>
        </div>
    </header>

    <section aria-label="Ringkasan exception" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="min-w-0 rounded-2xl border p-4 shadow-xs {{ $card['classes'] }}">
                <div class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full {{ $card['dot'] }}" aria-hidden="true"></span><p class="truncate text-[9px] font-black uppercase tracking-[0.14em]">{{ $card['label'] }}</p></div>
                <p class="mt-2 font-mono text-2xl font-black leading-none text-slate-950">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="filter-title" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs md:p-5">
        <div class="mb-4 flex items-center justify-between gap-3"><div><h2 id="filter-title" class="text-sm font-black text-slate-900">Saring perhatian</h2><p class="mt-0.5 text-[11px] text-slate-500">Tanggal memakai zona waktu aplikasi.</p></div>@if($hasRefinement)<span class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-rose-700">Filter aktif</span>@endif</div>
        <form method="GET" action="{{ route('admin.operational-exceptions.index') }}" class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter operational exceptions">
            <div class="min-w-0"><label for="date" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Tanggal</label><x-date-input name="date" :value="$filters['date']" :max="now(config('app.timezone'))->toDateString()" required wrapper-class="bg-white" /></div>
            <div class="min-w-0"><label for="severity" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Severity</label><select id="severity" name="severity" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"><option value="">Semua severity</option><option value="critical" @selected($filters['severity'] === 'critical')>Critical</option><option value="warning" @selected($filters['severity'] === 'warning')>Warning</option><option value="info" @selected($filters['severity'] === 'info')>Info</option></select></div>
            <div class="min-w-0"><label for="category" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Kategori</label><select id="category" name="category" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"><option value="">Semua kategori</option>@foreach($categories as $key => $category)<option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $category['label'] }}</option>@endforeach</select></div>
            <div class="min-w-0"><label for="employee_id" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Karyawan</label><select id="employee_id" name="employee_id" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"><option value="">Semua karyawan</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>{{ $employee->full_name }}</option>@endforeach</select></div>
            <div class="min-w-0"><label for="job_title_id" class="mb-1.5 block text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Jabatan</label><select id="job_title_id" name="job_title_id" class="min-h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"><option value="">Semua jabatan</option>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((string) $filters['job_title_id'] === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-5 lg:ml-auto lg:w-72"><a href="{{ route('admin.operational-exceptions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20">Reset</a><button type="submit" class="min-h-11 rounded-xl bg-rose-600 px-4 text-xs font-extrabold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/40 focus:ring-offset-2">Terapkan</button></div>
        </form>
        @error('date')<p class="mt-2 text-xs font-bold text-rose-700">{{ $message }}</p>@enderror
    </section>

    <section aria-labelledby="priority-list-title" class="space-y-3">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-rose-700">Urutan prioritas</p><h2 id="priority-list-title" class="mt-1 text-base font-black text-slate-950">Daftar kondisi</h2></div>@if($items->total() > 0)<p class="font-mono text-[11px] font-bold text-slate-500">{{ $items->firstItem() }}–{{ $items->lastItem() }} / {{ $items->total() }}</p>@endif</div>

        @if($items->isEmpty())
            <div class="lg:hidden">@include('admin.operational_exceptions._empty', ['hasRefinement' => $hasRefinement])</div>
            <div class="hidden lg:block">@include('admin.operational_exceptions._empty', ['hasRefinement' => $hasRefinement])</div>
        @else
            <div class="space-y-3 lg:hidden">@foreach($items as $item) @include('admin.operational_exceptions._item', ['item' => $item, 'variant' => 'mobile']) @endforeach</div>
            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs lg:block">
                <table class="w-full table-fixed text-left"><thead class="border-b border-slate-200 bg-slate-50"><tr class="text-[9px] font-black uppercase tracking-[0.13em] text-slate-500"><th class="w-[13%] px-4 py-3">Severity</th><th class="w-[30%] px-4 py-3">Kondisi</th><th class="w-[19%] px-4 py-3">Karyawan</th><th class="w-[25%] px-4 py-3">Fakta Kunci</th><th class="w-[13%] px-4 py-3 text-right">Tujuan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                            @php
                                $style = $severityStyles[$item['severity']] ?? $severityStyles['info'];
                            @endphp
                            <tr class="border-l-4 align-top transition hover:bg-slate-50/80 {{ $style['rail'] }}">
                                <td class="px-4 py-4"><span class="inline-flex rounded-md border px-2 py-1 text-[9px] font-black uppercase tracking-[0.12em] {{ $style['badge'] }}">{{ $item['severity'] }}</span></td>
                                <td class="min-w-0 px-4 py-4"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ mb_strtoupper($item['category_label']) }}</p><p class="mt-1 break-words text-xs font-extrabold leading-relaxed text-slate-900">{{ $item['message'] }}</p></td>
                                <td class="min-w-0 px-4 py-4">@if($item['employee_name'])<p class="break-words text-xs font-extrabold text-slate-800">{{ $item['employee_name'] }}</p><p class="mt-1 break-words text-[10px] font-semibold text-slate-500">{{ $item['employee_code'] }}@if($item['job_title']) · {{ $item['job_title'] }}@endif</p>@else<span class="text-xs font-semibold text-slate-400">Sistem</span>@endif</td>
                                <td class="min-w-0 px-4 py-4">@include('admin.operational_exceptions._facts', ['item' => $item])</td>
                                <td class="px-4 py-4 text-right">@if($item['action_url'] && $item['action_label'])<a href="{!! $item['action_url'] !!}" class="inline-flex min-h-11 items-center justify-end text-xs font-extrabold text-rose-700 hover:text-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500/30">{{ $item['action_label'] }} →</a>@endif</td>
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
