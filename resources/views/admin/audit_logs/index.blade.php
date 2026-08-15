@extends('layouts.admin')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail / Riwayat Perubahan')

@section('content')
@php
    $labels = [
        'check_in_at' => 'Jam Masuk', 'check_out_at' => 'Jam Pulang', 'status' => 'Status',
        'late_minutes' => 'Terlambat', 'worked_minutes' => 'Menit Kerja',
        'early_leave_minutes' => 'Pulang Awal', 'overtime_minutes' => 'Kandidat Lembur',
        'actual_minutes' => 'Menit Aktual', 'credited_minutes' => 'Menit Dikreditkan',
        'completion_source' => 'Sumber Penyelesaian',
        'date' => 'Tanggal', 'type' => 'Jenis Kalender', 'name' => 'Nama',
        'description' => 'Keterangan', 'is_working_day' => 'Hari Kerja',
        'employee_id' => 'Karyawan', 'override_type' => 'Jenis Override',
        'shift_id' => 'Shift', 'reason' => 'Alasan Jadwal',
    ];
@endphp
<div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs transition-colors">
        <div class="mb-4">
            <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Riwayat perubahan sensitif</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Append-only; record tidak dapat diedit atau dihapus dari aplikasi.</p>
        </div>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="w-full min-w-0 max-w-full"><x-date-input name="date_from" id="audit_date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="Tanggal mulai" /></div>
            <div class="w-full min-w-0 max-w-full"><x-date-input name="date_to" id="audit_date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="Tanggal akhir" /></div>
            <select name="user_id" class="min-w-0 min-h-[44px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 px-3 text-xs">
                <option value="">Semua actor</option>
                @foreach($actors as $actor)<option value="{{ $actor->id }}" @selected(($filters['user_id'] ?? '') == $actor->id)>{{ $actor->name }}</option>@endforeach
            </select>
            <select name="module" class="min-w-0 min-h-[44px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 px-3 text-xs">
                <option value="">Semua modul</option>
                <option value="attendance" @selected(($filters['module'] ?? '') === 'attendance')>Attendance</option>
                <option value="overtime" @selected(($filters['module'] ?? '') === 'overtime')>Overtime</option>
                <option value="work_calendar" @selected(($filters['module'] ?? '') === 'work_calendar')>Kalender Kerja</option>
                <option value="schedule_override" @selected(($filters['module'] ?? '') === 'schedule_override')>Jadwal Khusus</option>
                <option value="employee" @selected(($filters['module'] ?? '') === 'employee')>Partisipasi Karyawan</option>
            </select>
            <select name="employee_id" class="min-w-0 min-h-[44px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 px-3 text-xs">
                <option value="">Semua karyawan</option>
                @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>{{ $employee->full_name }}</option>@endforeach
            </select>
            <div class="flex gap-2">
                <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Action" class="min-w-0 flex-1 min-h-[44px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 text-xs">
                <button class="min-h-[44px] rounded-xl bg-slate-900 dark:bg-slate-700 px-4 text-xs font-bold text-white hover:bg-slate-800 dark:hover:bg-slate-600">Filter</button>
            </div>
        </form>
        @if(array_filter($filters))
            <a href="{{ route('admin.audit-logs.index') }}" class="mt-3 inline-block text-xs font-bold text-rose-600 dark:text-rose-400">Reset filter</a>
        @endif
    </div>

    <div class="space-y-3">
        @forelse($logs as $log)
            @php
                $employee = isset($log->metadata['employee_id']) ? $employees->get($log->metadata['employee_id']) : null;
                $before = $log->before_data ?? [];
                $after = $log->after_data ?? [];
                $changed = collect(array_unique(array_merge(array_keys($before), array_keys($after))))
                    ->filter(fn ($key) => array_key_exists($key, $labels) && ($before[$key] ?? null) != ($after[$key] ?? null));
            @endphp
            <article class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs transition-colors">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/60 px-2 py-1 text-[10px] font-extrabold text-rose-800 dark:text-rose-300">{{ $log->action }}</span>
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ $employee?->full_name ?? class_basename($log->auditable_type).' #'.$log->auditable_id }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400"><strong class="text-slate-800 dark:text-slate-200">{{ $log->user?->name ?? 'System' }}</strong> · {{ $log->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') }}</p>
                        @if($log->reason)<p class="mt-1 text-xs text-slate-700 dark:text-slate-300"><strong>Alasan:</strong> {{ $log->reason }}</p>@endif
                    </div>
                    <details class="shrink-0">
                        <summary class="flex min-h-[44px] cursor-pointer items-center rounded-xl bg-slate-900 dark:bg-slate-800 px-4 text-xs font-bold text-white hover:bg-slate-800 dark:hover:bg-slate-700">Lihat perubahan</summary>
                        <div class="mt-3 grid gap-2 sm:min-w-[520px] sm:grid-cols-2">
                            @forelse($changed as $field)
                                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3 sm:col-span-2">
                                    <p class="text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400">{{ $labels[$field] }}</p>
                                    <div class="mt-1 grid grid-cols-2 gap-3 text-xs">
                                        <div><span class="text-[10px] text-rose-600 dark:text-rose-400 font-bold">Sebelum</span><p class="break-words font-semibold text-slate-900 dark:text-slate-100">{{ is_scalar($before[$field] ?? null) ? ($before[$field] ?? '—') : '—' }}</p></div>
                                        <div><span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">Sesudah</span><p class="break-words font-semibold text-slate-900 dark:text-slate-100">{{ is_scalar($after[$field] ?? null) ? ($after[$field] ?? '—') : '—' }}</p></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 dark:text-slate-400 sm:col-span-2">Tidak ada field operasional yang berubah pada snapshot ini.</p>
                            @endforelse
                        </div>
                    </details>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center text-xs text-slate-500 dark:text-slate-400">Belum ada audit log untuk filter ini.</div>
        @endforelse
    </div>
    {{ $logs->links() }}
</div>
@endsection
