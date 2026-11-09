@php
    $selectedMode = old('outlet_access_mode', $selectedMode ?? 'selected');
    $rawSelectedIds = old('assigned_outlet_ids', $selectedIds ?? []);
    $selectedIds = is_array($rawSelectedIds) ? array_map('intval', $rawSelectedIds) : collect($rawSelectedIds)->map(fn ($id) => (int) $id)->all();
@endphp

<section id="admin-outlet-access-section" class="hidden rounded-2xl border border-teal-200/80 bg-teal-50/30 p-4 sm:p-5 dark:border-teal-900/50 dark:bg-teal-950/20">
    <div class="flex items-start gap-3">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-600/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <div>
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Akses Outlet Admin</h5>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Memperluas cakupan outlet tanpa mengubah izin role Admin.</p>
        </div>
    </div>

    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs transition hover:border-teal-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-teal-700">
            <input type="radio" name="outlet_access_mode" value="selected" {{ $selectedMode === 'selected' ? 'checked' : '' }} class="mt-0.5 h-4 w-4 text-teal-600 focus:ring-teal-500">
            <div class="min-w-0">
                <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Outlet Tertentu</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">Akses hanya outlet yang dicentang.</span>
            </div>
        </label>
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs transition hover:border-teal-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-teal-700">
            <input type="radio" name="outlet_access_mode" value="all" {{ $selectedMode === 'all' ? 'checked' : '' }} class="mt-0.5 h-4 w-4 text-teal-600 focus:ring-teal-500">
            <div class="min-w-0">
                <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Semua Outlet</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">Termasuk outlet baru di masa mendatang.</span>
            </div>
        </label>
    </div>

    <div id="assigned-outlets-section" class="mt-4 pt-3 border-t border-teal-100 dark:border-teal-900/40">
        <p class="mb-2.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Assigned Outlets</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach($outlets as $outlet)
                <label class="flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-xs transition hover:border-teal-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-teal-700">
                    <input type="checkbox" name="assigned_outlet_ids[]" value="{{ $outlet->id }}" {{ in_array((int) $outlet->id, $selectedIds, true) ? 'checked' : '' }} class="h-4 w-4 rounded text-teal-600 focus:ring-teal-500">
                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $outlet->name }} <span class="font-normal text-slate-400 dark:text-slate-500">({{ $outlet->code }})</span></span>
                </label>
            @endforeach
        </div>
        <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">Tanpa outlet terpilih, Admin akan gagal tertutup dan tidak dapat mengakses data operasional.</p>
        @error('assigned_outlet_ids')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>
</section>
