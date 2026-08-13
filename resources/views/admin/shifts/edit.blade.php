@extends('layouts.admin')

@section('title', 'Edit Shift Kerja')
@section('page-title', 'Edit Shift Kerja')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.shifts.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
            &larr; Kembali ke Daftar Shift
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8 space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">Edit Shift: {{ $shift->name }}</h3>
            <p class="text-xs text-slate-500">Kode Shift: <span class="font-mono font-bold text-rose-600">{{ $shift->code }}</span></p>
        </div>

        <form action="{{ route('admin.shifts.update', $shift) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nama Shift -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Shift *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $shift->name) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('name')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Shift -->
                <div>
                    <label for="code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Shift Unik * (Kapital)</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $shift->code) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-bold uppercase focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('code')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jam Mulai -->
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jam Mulai Shift * (HH:MM)</label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time', substr($shift->start_time, 0, 5)) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('start_time')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jam Selesai -->
                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jam Selesai Shift * (HH:MM)</label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time', substr($shift->end_time, 0, 5)) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('end_time')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Grace Period -->
                <div>
                    <label for="grace_period_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Toleransi Keterlambatan (Menit) *</label>
                    <input type="number" name="grace_period_minutes" id="grace_period_minutes" value="{{ old('grace_period_minutes', $shift->grace_period_minutes) }}" required min="0" max="240" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('grace_period_minutes')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Durasi Istirahat -->
                <div>
                    <label for="break_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Durasi Istirahat (Menit) *</label>
                    <input type="number" name="break_minutes" id="break_minutes" value="{{ old('break_minutes', $shift->break_minutes) }}" required min="0" max="480" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('break_minutes')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <hr class="border-slate-200">

            <!-- Parameter Jendela Waktu Presensi -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Jendela Waktu Presensi (Check-in / Check-out Window)</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="check_in_open_minutes_before" class="block text-[11px] font-bold text-slate-600">Buka Check-in (Menit Sebelum)</label>
                        <input type="number" name="check_in_open_minutes_before" id="check_in_open_minutes_before" value="{{ old('check_in_open_minutes_before', $shift->check_in_open_minutes_before) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-slate-50/50">
                    </div>

                    <div>
                        <label for="check_in_close_minutes_after" class="block text-[11px] font-bold text-slate-600">Tutup Check-in (Menit Setelah)</label>
                        <input type="number" name="check_in_close_minutes_after" id="check_in_close_minutes_after" value="{{ old('check_in_close_minutes_after', $shift->check_in_close_minutes_after) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-slate-50/50">
                    </div>

                    <div>
                        <label for="check_out_open_minutes_before" class="block text-[11px] font-bold text-slate-600">Buka Check-out (Menit Sebelum)</label>
                        <input type="number" name="check_out_open_minutes_before" id="check_out_open_minutes_before" value="{{ old('check_out_open_minutes_before', $shift->check_out_open_minutes_before) }}" required min="0" max="480" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-slate-50/50">
                    </div>
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $shift->is_active) ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">Status Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    Simpan Perubahan Shift
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
