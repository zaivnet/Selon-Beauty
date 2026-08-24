@php
    $actor = Auth::user();
    $outletScopeService = app(\App\Services\OutletScopeService::class);
    $isGlobal = $actor ? $outletScopeService->isGlobalScope($actor) : false;
    $inputOutletId = request()->has('outlet_id') ? (int) request('outlet_id') : null;
    $activeOutletId = $actor ? $outletScopeService->resolveRequestedOutlet($actor, $inputOutletId) : null;
    $activeOutlets = $actor ? $outletScopeService->getAuthorizedActiveOutlets($actor) : collect();
    $canSelect = $isGlobal || ($actor?->role === 'admin' && $activeOutlets->count() > 1);
    $activeOutlet = $activeOutletId ? $activeOutlets->firstWhere('id', $activeOutletId) : null;
@endphp

@if($canSelect)
    <form method="GET" action="{{ url()->current() }}" class="inline-flex items-center gap-2 w-full sm:w-auto" id="outletFilterForm">
        @foreach(request()->except(['outlet_id', 'page']) as $key => $value)
            @if(is_array($value))
                @foreach($value as $subValue)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $subValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="relative flex items-center w-full sm:w-auto">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10 text-slate-400 dark:text-slate-500" aria-hidden="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <select name="outlet_id" onchange="this.form.submit()" class="ui-select ui-select-icon !pl-10 !pr-8 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:border-rose-500 dark:hover:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 transition-all cursor-pointer w-full sm:w-auto">
                @if($isGlobal)
                    <option value="0" {{ $activeOutletId === null ? 'selected' : '' }}>Semua Outlet</option>
                @endif
                @foreach($activeOutlets as $outlet)
                    <option value="{{ $outlet->id }}" {{ $activeOutletId === $outlet->id ? 'selected' : '' }}>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
@elseif($activeOutlet)
    <div class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs font-bold text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80 shadow-xs w-full sm:w-auto justify-center sm:justify-start">
        <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <span>Outlet: {{ $activeOutlet->name }}</span>
    </div>
@elseif($actor?->role === 'admin')
    <div class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300 w-full sm:w-auto justify-center sm:justify-start">
        Belum ada outlet yang ditugaskan
    </div>
@endif
