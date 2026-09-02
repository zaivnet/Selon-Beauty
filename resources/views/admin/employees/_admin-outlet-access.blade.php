@php
    $selectedMode = old('outlet_access_mode', $selectedMode ?? 'selected');
    $rawSelectedIds = old('assigned_outlet_ids', $selectedIds ?? []);
    $selectedIds = is_array($rawSelectedIds) ? array_map('intval', $rawSelectedIds) : collect($rawSelectedIds)->map(fn ($id) => (int) $id)->all();
@endphp

<section id="admin-outlet-access-section" class="hidden rounded-xl border border-rose-200 bg-white p-4 dark:border-rose-900/60 dark:bg-slate-900">
    <div>
        <h5 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Akses Outlet Admin</h5>
        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Memperluas cakupan outlet tanpa mengubah izin role Admin.</p>
    </div>

    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
            <input type="radio" name="outlet_access_mode" value="selected" {{ $selectedMode === 'selected' ? 'checked' : '' }} class="mt-0.5 text-rose-600 focus:ring-rose-500">
            <span><span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Outlet Tertentu</span><span class="text-[11px] text-slate-500 dark:text-slate-400">Akses hanya outlet yang dicentang.</span></span>
        </label>
        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
            <input type="radio" name="outlet_access_mode" value="all" {{ $selectedMode === 'all' ? 'checked' : '' }} class="mt-0.5 text-rose-600 focus:ring-rose-500">
            <span><span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Semua Outlet</span><span class="text-[11px] text-slate-500 dark:text-slate-400">Termasuk outlet baru di masa mendatang.</span></span>
        </label>
    </div>

    <div id="assigned-outlets-section" class="mt-3">
        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Assigned Outlets</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach($outlets as $outlet)
                <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <input type="checkbox" name="assigned_outlet_ids[]" value="{{ $outlet->id }}" {{ in_array((int) $outlet->id, $selectedIds, true) ? 'checked' : '' }} class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $outlet->name }} ({{ $outlet->code }})</span>
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Tanpa outlet terpilih, Admin akan gagal tertutup dan tidak dapat mengakses data operasional.</p>
        @error('assigned_outlet_ids')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>
</section>
