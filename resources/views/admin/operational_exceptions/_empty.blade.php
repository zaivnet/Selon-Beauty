<div class="rounded-2xl border {{ $hasRefinement ? 'border-slate-200 bg-white text-slate-700' : 'border-emerald-200 bg-emerald-50 text-emerald-900' }} p-6 text-center shadow-xs">
    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl {{ $hasRefinement ? 'bg-slate-100' : 'bg-emerald-600 text-white' }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h3 class="mt-3 text-sm font-black">{{ $hasRefinement ? 'Tidak ada hasil untuk filter ini' : 'Operasional hari ini aman' }}</h3>
    <p class="mx-auto mt-1 max-w-md text-xs font-medium leading-relaxed opacity-75">{{ $hasRefinement ? 'Ubah atau reset filter untuk melihat kondisi operasional lainnya.' : 'Tidak ada masalah yang memerlukan perhatian.' }}</p>
</div>
