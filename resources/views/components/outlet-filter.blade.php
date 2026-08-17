@php
    $actor = Auth::user();
    $outletScopeService = app(\App\Services\OutletScopeService::class);
    $isGlobal = $actor ? $outletScopeService->isGlobalScope($actor) : false;
    $inputOutletId = request()->has('outlet_id') ? (int) request('outlet_id') : null;
    $activeOutletId = $actor ? $outletScopeService->resolveRequestedOutlet($actor, $inputOutletId) : null;
    $activeOutlets = $isGlobal ? $outletScopeService->getActiveOutlets() : collect();
    $adminOutletId = $actor ? $outletScopeService->getAdminOutletId($actor) : null;
    $adminOutlet = $adminOutletId ? \App\Models\Outlet::find($adminOutletId) : null;
@endphp

@if($isGlobal)
    <form method="GET" action="{{ url()->current() }}" class="inline-flex items-center gap-2" id="outletFilterForm">
        @foreach(request()->except(['outlet_id', 'page']) as $key => $value)
            @if(is_array($value))
                @foreach($value as $subValue)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $subValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="relative flex items-center">
            <span class="absolute left-3 text-slate-400 dark:text-slate-500 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <select name="outlet_id" onchange="this.form.submit()" class="pl-9 pr-8 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-sm hover:border-rose-500 dark:hover:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 transition-all cursor-pointer">
                <option value="0" {{ $activeOutletId === null ? 'selected' : '' }}>Semua Outlet</option>
                @foreach($activeOutlets as $outlet)
                    <option value="{{ $outlet->id }}" {{ $activeOutletId === $outlet->id ? 'selected' : '' }}>
                        {{ $outlet->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
@elseif($adminOutlet)
    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs font-bold text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <span>Outlet: {{ $adminOutlet->name }}</span>
    </div>
@endif
