@extends('layouts.admin')

@section('title', 'Kelola Shift Kerja')
@section('page-title', 'Daftar Shift Kerja')

@section('content')
<div class="space-y-6">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="flex items-center gap-2.5 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs font-semibold">
            <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-2.5 p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs font-semibold">
            <svg class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header Actions & Search Filter Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        
        <!-- Search & Status Filter Form -->
        <form action="{{ route('admin.shifts.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 flex-1">
            <div class="relative w-full sm:max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau kode shift..." class="w-full pl-10 pr-3.5 py-2.5 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
            </div>

            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3.5 py-2.5 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 ui-select">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit" class="w-full sm:w-auto min-h-[42px] px-4 py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                Filter
            </button>
            
            @if($search || $status)
                <a href="{{ route('admin.shifts.index') }}" class="text-xs text-teal-600 dark:text-teal-400 font-semibold hover:underline">Reset</a>
            @endif
        </form>

        <!-- Add Shift CTA Button -->
        <div>
            <a href="{{ route('admin.shifts.create') }}" class="inline-flex min-h-[42px] items-center justify-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Shift Baru</span>
            </a>
        </div>
    </div>

    <!-- Shift Listing Container -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs p-5 space-y-4 transition-colors">
        
        @if($shifts->isEmpty())
            <!-- Clean Empty State when no shifts exist -->
            <div class="text-center py-14 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-800/30">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center mx-auto mb-3 shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Shift Kerja</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
                    Tambahkan shift kerja pertama untuk mengatur jam operasional toko.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.shifts.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                        + Tambah Shift Pertama
                    </a>
                </div>
            </div>
        @else
            <!-- Desktop Table View (Hidden on Mobile) -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50 dark:bg-slate-800/60">
                            <th class="p-3.5">Kode</th>
                            <th class="p-3.5">Nama Shift</th>
                            <th class="p-3.5">Jam Kerja</th>
                            <th class="p-3.5">Grace Period</th>
                            <th class="p-3.5">Istirahat</th>
                            <th class="p-3.5">Lintas Hari</th>
                            <th class="p-3.5">Auto Checkout</th>
                            <th class="p-3.5">Status</th>
                            <th class="p-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($shifts as $sf)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-teal-50 text-teal-700 border border-teal-200/80 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800/60">
                                        {{ $sf->code }}
                                    </span>
                                </td>
                                <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $sf->name }}</td>
                                <td class="p-3.5 font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $sf->formatted_work_hours }}
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-normal">({{ floor($sf->work_duration_minutes / 60) }} jam {{ $sf->work_duration_minutes % 60 }} m)</div>
                                </td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400 font-semibold">{{ $sf->grace_period_minutes }} Menit</td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $sf->break_minutes }} Menit</td>
                                <td class="p-3.5">
                                    @if($sf->crosses_midnight)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60 font-bold text-[10px] rounded-full">
                                            🌙 Ya (Tengah Malam)
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">Tidak</span>
                                    @endif
                                </td>
                                <td class="p-3.5">
                                    @if($sf->auto_checkout_enabled)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                            Aktif (+{{ $sf->auto_checkout_grace_minutes }}m)
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="p-3.5">
                                    @if($sf->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 font-bold text-[10px] rounded-full">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[10px] rounded-full">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-right space-x-2">
                                    <a href="{{ route('admin.shifts.show', $sf) }}" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-semibold underline">Detail</a>
                                    <a href="{{ route('admin.shifts.edit', $sf) }}" class="text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 font-semibold underline">Edit</a>
                                    <form action="{{ route('admin.shifts.toggle-status', $sf) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 font-semibold underline cursor-pointer">
                                            {{ $sf->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Visible on Mobile) -->
            <div class="md:hidden space-y-3">
                @foreach($shifts as $sf)
                    <div class="bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800/60">{{ $sf->code }}</span>
                            @if($sf->is_active)
                                <span class="px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 font-bold text-[10px] rounded-full">Aktif</span>
                            @else
                                <span class="px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[10px] rounded-full">Nonaktif</span>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $sf->name }}</h4>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">Jam: {{ $sf->formatted_work_hours }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/80 dark:border-slate-700/80">
                            <div>Grace Period: <strong class="text-slate-800 dark:text-slate-200">{{ $sf->grace_period_minutes }}m</strong></div>
                            <div>Istirahat: <strong class="text-slate-800 dark:text-slate-200">{{ $sf->break_minutes }}m</strong></div>
                        </div>

                        @if($sf->auto_checkout_enabled)
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                Auto checkout: <strong class="text-emerald-700 dark:text-emerald-400">+{{ $sf->auto_checkout_grace_minutes }}m</strong> setelah shift
                            </div>
                        @endif

                        <div class="text-xs pt-2.5 border-t border-slate-200/80 dark:border-slate-700/80 flex justify-between items-center">
                            @if($sf->crosses_midnight)
                                <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300">🌙 Lintas Tengah Malam</span>
                            @else
                                <span></span>
                            @endif

                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.shifts.show', $sf) }}" class="min-h-[44px] inline-flex items-center text-slate-700 dark:text-slate-300 font-bold underline">Detail</a>
                                <a href="{{ route('admin.shifts.edit', $sf) }}" class="min-h-[44px] inline-flex items-center text-teal-600 dark:text-teal-400 font-bold underline">Edit</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="pt-3">
                {{ $shifts->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
