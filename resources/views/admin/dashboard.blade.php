@extends('layouts.admin')

@section('title', 'Status Operasional')
@section('page-title', 'Dashboard')

@section('content')
@php
    $summaryCards = [
        ['label' => 'Critical', 'value' => $exceptions['summary']['critical'], 'tone' => 'rose', 'hint' => 'Perlu tindakan segera'],
        ['label' => 'Warning', 'value' => $exceptions['summary']['warning'], 'tone' => 'amber', 'hint' => 'Perlu dipantau'],
        ['label' => 'Pending Approval', 'value' => $exceptions['summary']['pending_approval'], 'tone' => 'indigo', 'hint' => 'Menunggu keputusan'],
        ['label' => 'Active Overtime', 'value' => $exceptions['summary']['active_overtime'], 'tone' => 'emerald', 'hint' => 'Sesi sedang berjalan'],
    ];
    $toneClasses = [
        'rose' => ['card' => 'border-rose-200 bg-rose-50/70', 'label' => 'text-rose-800', 'dot' => 'bg-rose-600'],
        'amber' => ['card' => 'border-amber-200 bg-amber-50/70', 'label' => 'text-amber-900', 'dot' => 'bg-amber-500'],
        'indigo' => ['card' => 'border-indigo-200 bg-indigo-50/70', 'label' => 'text-indigo-800', 'dot' => 'bg-indigo-600'],
        'emerald' => ['card' => 'border-emerald-200 bg-emerald-50/70', 'label' => 'text-emerald-800', 'dot' => 'bg-emerald-600'],
    ];
    $previewCategoryKeys = collect($exceptions['items'])->pluck('category')->unique()->take(4);
    $previewGroups = $previewCategoryKeys->map(fn ($key) => $exceptions['groups'][$key] ?? null)->filter();
@endphp

<div class="space-y-5 md:space-y-6">
    <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="h-1 bg-rose-600"></div>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between md:p-6">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-rose-700">Operational control</p>
                <h1 class="mt-1 text-xl font-black tracking-tight text-slate-950 md:text-2xl">Status Operasional Hari Ini</h1>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-semibold text-slate-500">
                    <span>{{ $exceptions['date_label'] }}</span><span aria-hidden="true" class="text-slate-300">•</span>
                    <span>Diperiksa <span class="font-mono text-slate-700">{{ $exceptions['generated_at']->format('H:i') }}</span></span>
                </div>
            </div>
            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 text-xs font-extrabold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </a>
                <a href="{{ route('admin.operational-exceptions.index', ['date' => $exceptions['date']]) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 text-xs font-extrabold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/40 focus:ring-offset-2">
                    Pusat Perhatian
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </header>

    <section aria-label="Ringkasan status operasional" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($summaryCards as $card)
            @php
                $tone = $toneClasses[$card['tone']];
            @endphp
            <article class="min-w-0 rounded-2xl border p-4 shadow-xs {{ $tone['card'] }}">
                <div class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full {{ $tone['dot'] }}" aria-hidden="true"></span><p class="truncate text-[9px] font-black uppercase tracking-[0.14em] {{ $tone['label'] }}">{{ $card['label'] }}</p></div>
                <p class="mt-2 font-mono text-2xl font-black leading-none text-slate-950">{{ $card['value'] }}</p>
                <p class="mt-2 text-[10px] font-semibold leading-snug text-slate-500">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </section>

    @if($exceptions['backup_health']['available'])
        @php
            $backupCritical = $exceptions['backup_health']['severity'] === 'critical';
            $backupTone = $backupCritical ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900';
        @endphp
        <aside class="flex flex-col gap-3 rounded-2xl border px-4 py-3 sm:flex-row sm:items-center sm:justify-between {{ $backupTone }}" aria-label="Status backup">
            <div class="flex min-w-0 items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7a2 2 0 012-2h12a2 2 0 012 2M4 7h16M9 11h6"/></svg>
                <div class="min-w-0"><p class="text-[10px] font-black uppercase tracking-[0.14em]">Backup operasional</p><p class="mt-0.5 break-words text-xs font-bold">{{ $exceptions['backup_health']['message'] }}</p>
                    @if($exceptions['backup_health']['last_successful_at'])<p class="mt-1 text-[10px] font-semibold opacity-75">Berhasil terakhir <span class="font-mono">{{ $exceptions['backup_health']['last_successful_at']->format('d M Y H:i') }}</span></p>@endif
                </div>
            </div>
            @if(in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                <a href="{{ route('admin.settings.backups.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-current/20 bg-white/70 px-4 text-xs font-extrabold transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-current/20">Buka Backup</a>
            @endif
        </aside>
    @endif

    <section aria-labelledby="attention-title" class="rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-end sm:justify-between md:px-6">
            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-rose-700">Prioritas sekarang</p><h2 id="attention-title" class="mt-1 text-lg font-black text-slate-950">Perlu Perhatian</h2><p class="mt-1 text-xs text-slate-500">Pantau konteksnya di modul asal sebelum mengambil tindakan.</p></div>
            @if($exceptions['summary']['total'] > 0)<span class="font-mono text-xs font-bold text-slate-500">{{ $exceptions['summary']['total'] }} kondisi terdeteksi</span>@endif
        </div>

        @if($exceptions['summary']['total'] === 0)
            <div class="m-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900 md:m-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                <div><h3 class="text-sm font-black">Operasional hari ini aman</h3><p class="mt-1 text-xs font-medium leading-relaxed">Tidak ada masalah yang memerlukan perhatian.</p></div>
            </div>
        @else
            <div class="grid gap-4 p-4 md:p-6 lg:grid-cols-2">
                @foreach($previewGroups as $group)
                    @php
                        $severityStyle = match($group['severity']) {
                            'critical' => ['rail' => 'border-l-rose-600', 'badge' => 'border-rose-200 bg-rose-50 text-rose-800'],
                            'warning' => ['rail' => 'border-l-amber-500', 'badge' => 'border-amber-200 bg-amber-50 text-amber-900'],
                            default => ['rail' => 'border-l-indigo-500', 'badge' => 'border-indigo-200 bg-indigo-50 text-indigo-800'],
                        };
                    @endphp
                    <article class="min-w-0 overflow-hidden rounded-2xl border border-l-4 border-slate-200 bg-slate-50/50 {{ $severityStyle['rail'] }}">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200/70 px-4 py-3"><div class="min-w-0"><h3 class="truncate text-xs font-black text-slate-900">{{ mb_strtoupper($group['label']) }}</h3><p class="mt-0.5 text-[10px] font-semibold text-slate-500">{{ $group['count'] }} kondisi</p></div><span class="shrink-0 rounded-md border px-2 py-1 text-[9px] font-black uppercase tracking-[0.12em] {{ $severityStyle['badge'] }}">{{ $group['severity'] }}</span></div>
                        <div class="divide-y divide-slate-200/70">@foreach(collect($group['items'])->take(2) as $item) @include('admin.operational_exceptions._item', ['item' => $item, 'variant' => 'dashboard']) @endforeach</div>
                        <a href="{{ route('admin.operational-exceptions.index', ['date' => $exceptions['date'], 'category' => $group['key']]) }}" class="flex min-h-11 items-center justify-center border-t border-slate-200 bg-white px-4 text-xs font-extrabold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-rose-500/30">Lihat Semua {{ $group['label'] }}</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
