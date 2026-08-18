@extends('layouts.admin')

@section('title', 'Kelola Shift Kerja')
@section('page-title', 'Daftar Shift Kerja')

@section('content')
<div class="space-y-6">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Actions & Search Filter Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
        
        <!-- Search & Status Filter Form -->
        <form action="{{ route('admin.shifts.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 flex-1">
            <div class="relative w-full sm:max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau kode shift..." class="w-full pl-9 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
            </div>

            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 ui-select">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white rounded-xl text-xs font-semibold hover:bg-slate-900 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                Filter
            </button>
            
            @if($search || $status)
                <a href="{{ route('admin.shifts.index') }}" class="text-xs text-rose-600 dark:text-rose-400 font-semibold underline">Reset</a>
            @endif
        </form>

        <!-- Add Shift CTA Button -->
        <div>
            <a href="{{ route('admin.shifts.create') }}" class="px-4 py-2 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold text-xs rounded-xl shadow-xs hover:from-rose-700 hover:to-pink-700 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Shift Baru</span>
            </a>
        </div>
    </div>

    <!-- Shift Listing Container -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 space-y-4 transition-colors">
        
        @if($shifts->isEmpty())
            <!-- Clean Empty State when no shifts exist -->
            <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/40">
                <div class="w-14 h-14 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Shift Kerja</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
                    Tambahkan shift kerja pertama untuk mengatur jam operasional toko.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.shifts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white font-bold text-xs rounded-xl shadow-xs hover:bg-rose-700 transition-colors ui-btn ui-btn-primary">
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
                            <th class="p-3">Kode</th>
                            <th class="p-3">Nama Shift</th>
                            <th class="p-3">Jam Kerja</th>
                            <th class="p-3">Grace Period</th>
                            <th class="p-3">Istirahat</th>
                            <th class="p-3">Lintas Hari</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($shifts as $sf)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="p-3 font-mono font-bold text-rose-600 dark:text-rose-400">{{ $sf->code }}</td>
                                <td class="p-3 font-bold text-slate-800 dark:text-slate-200">{{ $sf->name }}</td>
                                <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $sf->formatted_work_hours }}
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-normal">({{ floor($sf->work_duration_minutes / 60) }} jam {{ $sf->work_duration_minutes % 60 }} m)</div>
                                </td>
                                <td class="p-3 text-slate-600 dark:text-slate-400 font-semibold">{{ $sf->grace_period_minutes }} Menit</td>
                                <td class="p-3 text-slate-600 dark:text-slate-400">{{ $sf->break_minutes }} Menit</td>
                                <td class="p-3">
                                    @if($sf->crosses_midnight)
                                        <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-bold text-[10px] rounded-full">
                                            🌙 Ya (Tengah Malam)
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">Tidak</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if($sf->is_active)
                                        <span class="px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-bold text-[10px] rounded-full">Aktif</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[10px] rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-2">
                                    <a href="{{ route('admin.shifts.show', $sf) }}" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-semibold underline">Detail</a>
                                    <a href="{{ route('admin.shifts.edit', $sf) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold underline">Edit</a>
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
                    <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-rose-600 dark:text-rose-400">{{ $sf->code }}</span>
                            @if($sf->is_active)
                                <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-bold text-[10px] rounded-full">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[10px] rounded-full">Nonaktif</span>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $sf->name }}</h4>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">Jam: {{ $sf->formatted_work_hours }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-700">
                            <div>Grace Period: <strong>{{ $sf->grace_period_minutes }}m</strong></div>
                            <div>Istirahat: <strong>{{ $sf->break_minutes }}m</strong></div>
                        </div>

                        <div class="text-xs pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            @if($sf->crosses_midnight)
                                <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300">🌙 Lintas Tengah Malam</span>
                            @else
                                <span></span>
                            @endif

                            <div class="space-x-2">
                                <a href="{{ route('admin.shifts.show', $sf) }}" class="text-slate-700 dark:text-slate-300 font-bold underline">Detail</a>
                                <a href="{{ route('admin.shifts.edit', $sf) }}" class="text-blue-600 dark:text-blue-400 font-bold underline">Edit</a>
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
