@php
    $variant = $variant ?? 'mobile';
    $severityStyles = [
        'critical' => ['rail' => 'border-l-rose-600', 'badge' => 'border-rose-200 bg-rose-50 text-rose-800'],
        'warning' => ['rail' => 'border-l-amber-500', 'badge' => 'border-amber-200 bg-amber-50 text-amber-900'],
        'info' => ['rail' => 'border-l-indigo-500', 'badge' => 'border-indigo-200 bg-indigo-50 text-indigo-800'],
    ];
    $style = $severityStyles[$item['severity']] ?? $severityStyles['info'];
@endphp

@if($variant === 'dashboard')
    <div class="min-w-0 bg-white/70 p-4">
        <div class="flex min-w-0 items-start gap-3">
            <span class="mt-0.5 shrink-0 rounded-md border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $style['badge'] }}">{{ $item['severity'] }}</span>
            <div class="min-w-0 flex-1">
                <p class="break-words text-xs font-extrabold leading-relaxed text-slate-900">{{ $item['message'] }}</p>
                @if($item['employee_name'])<p class="mt-1 break-words text-[11px] font-semibold text-slate-500">{{ $item['employee_name'] }}@if($item['job_title']) · {{ $item['job_title'] }}@endif</p>@endif
                <div class="mt-2">@include('admin.operational_exceptions._facts', ['item' => $item])</div>
                @if($item['action_url'] && $item['action_label'])<a href="{!! $item['action_url'] !!}" class="mt-3 inline-flex min-h-11 items-center text-xs font-extrabold text-rose-700 transition hover:text-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500/30">{{ $item['action_label'] }} <span aria-hidden="true" class="ml-1">→</span></a>@endif
            </div>
        </div>
    </div>
@else
    <article class="min-w-0 overflow-hidden rounded-2xl border border-l-4 border-slate-200 bg-white shadow-xs {{ $style['rail'] }}">
        <div class="space-y-3 p-4">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0"><p class="break-words text-[10px] font-black uppercase tracking-[0.12em] text-slate-500">{{ mb_strtoupper($item['category_label']) }}</p><h3 class="mt-1 break-words text-sm font-black leading-snug text-slate-950">{{ $item['message'] }}</h3></div>
                <span class="shrink-0 rounded-md border px-2 py-1 text-[9px] font-black uppercase tracking-[0.12em] {{ $style['badge'] }}">{{ $item['severity'] }}</span>
            </div>
            @if($item['employee_name'])<div class="min-w-0 border-l-2 border-slate-200 pl-3"><p class="break-words text-xs font-extrabold text-slate-800">{{ $item['employee_name'] }}</p><p class="mt-0.5 break-words text-[10px] font-semibold text-slate-500">{{ $item['employee_code'] }}@if($item['job_title']) · {{ $item['job_title'] }}@endif</p></div>@endif
            @include('admin.operational_exceptions._facts', ['item' => $item])
        </div>
        @if($item['action_url'] && $item['action_label'])<a href="{!! $item['action_url'] !!}" class="flex min-h-11 w-full items-center justify-between border-t border-slate-200 bg-slate-50 px-4 text-xs font-extrabold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-rose-500/30"><span>{{ $item['action_label'] }}</span><span aria-hidden="true">→</span></a>@endif
    </article>
@endif
