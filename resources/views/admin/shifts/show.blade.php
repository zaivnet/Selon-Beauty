@extends('layouts.admin')

@section('title', 'Detail Shift Kerja')
@section('page-title', 'Detail Parameter Shift Kerja')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('admin.shifts.index') }}" class="min-h-[44px] inline-flex items-center text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors gap-1.5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Shift</span>
        </a>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.shifts.edit', $shift) }}" class="min-h-[42px] inline-flex items-center justify-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                Edit Shift
            </a>
            <form action="{{ route('admin.shifts.toggle-status', $shift) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="min-h-[42px] px-4 py-2 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-colors cursor-pointer">
                    {{ $shift->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Shift Overview Banner -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
            <div>
                <span class="inline-flex items-center text-xs font-mono font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 border border-teal-200/80 dark:border-teal-800/60 px-3 py-1 rounded-lg">Kode: {{ $shift->code }}</span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 mt-2.5">{{ $shift->name }}</h2>
            </div>
            <div>
                @if($shift->is_active)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 font-bold text-xs rounded-full">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Status Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-xs rounded-full">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        Nonaktif
                    </span>
                @endif
            </div>
        </div>

        <!-- Shift Parameters Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Jam Kerja (Start - End)</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-sm mt-1 block">{{ $shift->formatted_work_hours }}</span>
                @if($shift->crosses_midnight)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 dark:text-indigo-300 mt-1.5 block">🌙 Shift Lintas Tengah Malam (Cross-midnight)</span>
                @endif
            </div>

            <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Durasi Kerja Bersih</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm mt-1 block">
                    {{ floor($shift->net_work_duration_minutes / 60) }} jam {{ $shift->net_work_duration_minutes % 60 }} menit
                </span>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block">Total Kotor: {{ floor($shift->work_duration_minutes / 60) }}j {{ $shift->work_duration_minutes % 60 }}m | Break: {{ $shift->break_minutes }}m</span>
            </div>

            <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Grace Period (Keterlambatan)</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-sm mt-1 block">{{ $shift->grace_period_minutes }} Menit</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block">Batas toleransi masuk tepat waktu.</span>
            </div>

            <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Durasi Istirahat</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm mt-1 block">{{ $shift->break_minutes }} Menit</span>
            </div>
        </div>

        <div class="bg-slate-50/70 dark:bg-slate-800/50 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-2.5 text-xs">
            <span class="font-bold text-slate-800 dark:text-slate-200 block uppercase tracking-wider text-[11px]">Batas Jendela Waktu Presensi:</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">• Buka Check-in: <strong class="text-slate-800 dark:text-slate-200">{{ $shift->check_in_open_minutes_before }} m</strong> sebelum shift</div>
                <div class="flex items-center gap-1.5">• Tutup Check-in: <strong class="text-slate-800 dark:text-slate-200">{{ $shift->check_in_close_minutes_after }} m</strong> setelah shift</div>
                <div class="flex items-center gap-1.5">• Buka Check-out: <strong class="text-slate-800 dark:text-slate-200">{{ $shift->check_out_open_minutes_before }} m</strong> sebelum selesai</div>
            </div>
        </div>

        <div class="p-4 sm:p-5 bg-slate-50/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
                <span class="font-bold text-slate-800 dark:text-slate-200 block uppercase tracking-wider text-[11px]">Automatic Shift Checkout:</span>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    @if($shift->auto_checkout_enabled)
                        Otomatis checkout setelah <strong>{{ $shift->auto_checkout_grace_minutes }} menit</strong> dari jam selesai shift ({{ $shift->auto_checkout_grace_minutes == 0 ? 'Tepat saat shift selesai' : '+'.$shift->auto_checkout_grace_minutes.' menit' }}).
                    @else
                        Fitur checkout otomatis dinonaktifkan untuk shift ini.
                    @endif
                </p>
            </div>
            <div>
                @if($shift->auto_checkout_enabled)
                    <span class="inline-flex items-center px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 font-bold text-[11px] rounded-full">Aktif ({{ $shift->auto_checkout_grace_minutes }}m)</span>
                @else
                    <span class="inline-flex items-center px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[11px] rounded-full">Nonaktif</span>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
            <form action="{{ route('admin.shifts.destroy', $shift) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus shift ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="min-h-[44px] inline-flex items-center text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 underline cursor-pointer">
                    Hapus Shift Kerja
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
