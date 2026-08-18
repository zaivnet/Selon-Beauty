@extends('layouts.admin')

@section('title', 'Kelola Outlet & Cabang')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight ui-page-header">Outlet & Cabang</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Kelola seluruh lokasi outlet fisik, penugasan admin, dan koordinat geofence absensi.</p>
        </div>
        <div>
            <a href="{{ route('admin.outlets.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-500/25 transition-all cursor-pointer ui-btn ui-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Outlet Baru
            </a>
        </div>
    </div>

    <!-- Alert Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Simulator Evaluasi Geofence Outlet -->
    @if($outlets->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>Simulator Evaluasi Geofence Per-Outlet</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Uji perhitungan jarak Haversine koordinat karyawan terhadap outlet yang dipilih secara instan.</p>
            </div>

            <form action="{{ route('admin.outlets.index') }}" method="GET" class="space-y-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">Pilih Outlet</label>
                        <select name="test_outlet_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-semibold ui-select">
                            @foreach($outlets as $o)
                                <option value="{{ $o->id }}" {{ request('test_outlet_id') == $o->id ? 'selected' : '' }}>{{ $o->name }} ({{ $o->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">Lat Karyawan</label>
                        <input type="number" step="any" name="test_lat" value="{{ request('test_lat', $outlets->first()?->latitude ?? -6.2) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">Lon Karyawan</label>
                        <input type="number" step="any" name="test_lon" value="{{ request('test_lon', $outlets->first()?->longitude ?? 106.8) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">Akurasi GPS (m)</label>
                        <input type="number" step="any" name="test_accuracy" value="{{ request('test_accuracy', 15) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-slate-900 dark:bg-rose-600 hover:bg-slate-800 dark:hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition-colors cursor-pointer ui-btn ui-btn-primary">
                        Hitung Jarak & Uji Status Geofence
                    </button>
                </div>
            </form>

            @if(isset($testResult) && $testResult)
                <div class="p-4 rounded-xl border {{ $testResult['is_valid'] ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60 text-rose-900 dark:text-rose-300' }} text-xs space-y-2">
                    <div class="flex items-center justify-between font-bold">
                        <span>Hasil Evaluasi Terhadap {{ $testResult['outlet']->name }} ({{ $testResult['outlet']->code }}):</span>
                        @if($testResult['is_valid'])
                            <span class="px-3 py-1 bg-emerald-600 text-white font-black rounded-full text-[10px]">✓ DALAM RADIUS (VALID)</span>
                        @else
                            <span class="px-3 py-1 bg-rose-600 text-white font-black rounded-full text-[10px] ui-btn ui-btn-primary">✕ DI LUAR RADIUS (DITOLAK)</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] pt-1">
                        <div>Jarak Terhitung: <strong class="font-mono font-bold text-slate-900 dark:text-white">{{ $testResult['distance_meters'] }} m</strong></div>
                        <div>Radius Terizin: <strong class="font-bold text-slate-900 dark:text-white">{{ $testResult['outlet']->radius_meters }} m</strong></div>
                        <div>Akurasi Terdeteksi: <strong class="font-bold text-slate-900 dark:text-white">{{ $testResult['test_accuracy'] }} m</strong></div>
                        <div>Maks Akurasi Outlet: <strong class="font-bold text-slate-900 dark:text-white">{{ $testResult['outlet']->max_accuracy_meters }} m</strong></div>
                    </div>
                    @if($testResult['error_message'])
                        <p class="font-bold text-rose-700 dark:text-rose-400 pt-2 border-t border-rose-200/60 dark:border-rose-800/60">
                            Detail Evaluasi: {{ $testResult['error_message'] }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Outlet Grid / Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto ui-table-container">
            <table class="w-full text-left border-collapse ui-table">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 px-4 sm:px-6">Kode & Nama Outlet</th>
                        <th class="py-3.5 px-4">Alamat Fisik</th>
                        <th class="py-3.5 px-4">Geofence GPS</th>
                        <th class="py-3.5 px-4 text-center">Admin & Karyawan</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @forelse($outlets as $outlet)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 font-black flex items-center justify-center text-xs shrink-0">
                                        {{ substr($outlet->code, 0, 3) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                                            <span>{{ $outlet->name }}</span>
                                            <span class="px-2 py-0.5 text-[10px] font-black rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ $outlet->code }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 max-w-xs truncate text-slate-600 dark:text-slate-400">
                                {{ $outlet->address ?: 'Alamat belum diisi' }}
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ number_format($outlet->latitude, 5) }}, {{ number_format($outlet->longitude, 5) }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Radius: <span class="font-bold text-rose-600 dark:text-rose-400">{{ $outlet->radius_meters }}m</span> (Akurasi: ±{{ $outlet->max_accuracy_meters }}m)
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-3 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700">
                                    <div class="text-center">
                                        <div class="text-xs font-black text-rose-600 dark:text-rose-400">{{ $outlet->employees_count }}</div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">Karyawan</div>
                                    </div>
                                    <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                                    <div class="text-center">
                                        <div class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $outlet->users_count }}</div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">Admin</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <form action="{{ route('admin.outlets.toggle-status', $outlet) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black cursor-pointer transition-all border {{ $outlet->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $outlet->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $outlet->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.outlets.edit', $outlet) }}" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Edit Outlet">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if($outlet->code !== 'PUSAT')
                                        <form action="{{ route('admin.outlets.destroy', $outlet) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus outlet {{ $outlet->name }}?')" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer" title="Hapus Outlet">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                Belum ada outlet terdaftar. Klik "Tambah Outlet Baru" untuk mulai menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
