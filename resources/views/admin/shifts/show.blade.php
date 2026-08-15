@extends('layouts.admin')

@section('title', 'Detail Shift Kerja')
@section('page-title', 'Detail Parameter Shift Kerja')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.shifts.index') }}" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 flex items-center gap-1">
            &larr; Kembali ke Daftar Shift
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.shifts.edit', $shift) }}" class="px-3.5 py-1.5 bg-blue-600 text-white font-bold text-xs rounded-lg hover:bg-blue-700 transition-colors">
                Edit Shift
            </a>
            <form action="{{ route('admin.shifts.toggle-status', $shift) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 bg-slate-800 dark:bg-slate-700 text-white font-bold text-xs rounded-lg hover:bg-slate-900 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                    {{ $shift->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Shift Overview Banner -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
            <div>
                <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 px-2.5 py-1 rounded-md">Kode: {{ $shift->code }}</span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 mt-2">{{ $shift->name }}</h2>
            </div>
            <div>
                @if($shift->is_active)
                    <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-bold text-xs rounded-full">Status Aktif</span>
                @else
                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-xs rounded-full">Nonaktif</span>
                @endif
            </div>
        </div>

        <!-- Shift Parameters Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Jam Kerja (Start - End)</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-sm mt-0.5 block">{{ $shift->formatted_work_hours }}</span>
                @if($shift->crosses_midnight)
                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 mt-1 block">🌙 Shift Lintas Tengah Malam (Cross-midnight)</span>
                @endif
            </div>

            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Durasi Kerja Bersih</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm mt-0.5 block">
                    {{ floor($shift->net_work_duration_minutes / 60) }} jam {{ $shift->net_work_duration_minutes % 60 }} menit
                </span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500">Total Kotor: {{ floor($shift->work_duration_minutes / 60) }}j {{ $shift->work_duration_minutes % 60 }}m | Break: {{ $shift->break_minutes }}m</span>
            </div>

            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Grace Period (Keterlambatan)</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-sm mt-0.5 block">{{ $shift->grace_period_minutes }} Menit</span>
                <span class="text-[10px] text-slate-500 dark:text-slate-400">Batas toleransi masuk tepat waktu.</span>
            </div>

            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Durasi Istirahat</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm mt-0.5 block">{{ $shift->break_minutes }} Menit</span>
            </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <span class="font-bold text-slate-800 dark:text-slate-200 block uppercase tracking-wider text-[11px]">Batas Jendela Waktu Presensi:</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-slate-600 dark:text-slate-400">
                <div>• Buka Check-in: <strong>{{ $shift->check_in_open_minutes_before }} m</strong> sebelum shift</div>
                <div>• Tutup Check-in: <strong>{{ $shift->check_in_close_minutes_after }} m</strong> setelah shift</div>
                <div>• Buka Check-out: <strong>{{ $shift->check_out_open_minutes_before }} m</strong> sebelum selesai</div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
            <form action="{{ route('admin.shifts.destroy', $shift) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus shift ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 underline cursor-pointer">
                    Hapus Shift Kerja
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
