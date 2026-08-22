@extends('layouts.admin')

@section('title', 'Kalender Kerja')
@section('page-title', 'Jadwal & Kalender')

@section('content')
@php
    $typeStyles = [
        'public_holiday' => ['label' => 'LIBUR NASIONAL', 'class' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'],
        'company_holiday' => ['label' => 'LIBUR PERUSAHAAN', 'class' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800/60'],
        'special_working_day' => ['label' => 'HARI KERJA KHUSUS', 'class' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60'],
    ];
@endphp

<div class="space-y-5" x-data="{ overrideType: '{{ old('override_type', 'off') }}' }">
    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs sm:p-5 transition-colors">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-rose-600 dark:text-rose-400">Sumber jadwal efektif</p>
                <h3 class="mt-1 text-lg font-black tracking-tight text-slate-950 dark:text-slate-100">Kalender Kerja & Jadwal Khusus</h3>
                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-400">Atur hari libur global tanpa mengubah template jadwal. Jadwal khusus karyawan selalu menjadi prioritas tertinggi.</p>
            </div>
            <a href="#tambah-kalender" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-rose-600 px-4 text-xs font-extrabold text-white shadow-xs transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 ui-btn ui-btn-primary">+ Tambah Tanggal</a>
        </div>

        <nav class="mt-4 flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 dark:bg-slate-800/70 p-1" aria-label="Navigasi penjadwalan">
            <a href="{{ route('admin.schedules.index') }}" class="min-h-[44px] shrink-0 rounded-lg px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 transition hover:bg-white dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100">Jadwal Mingguan</a>
            <span class="min-h-[44px] shrink-0 rounded-lg bg-white dark:bg-slate-900 px-4 py-3 text-center text-xs font-black text-rose-700 dark:text-rose-400 shadow-xs" aria-current="page">Kalender Kerja</span>
        </nav>
    </section>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 p-4 text-xs text-rose-800 dark:text-rose-300" role="alert">
            <p class="font-black">Data belum dapat disimpan.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Legenda kalender kerja">
        @foreach($typeStyles as $style)
            <div class="flex items-center gap-3 rounded-xl border {{ $style['class'] }} p-3">
                <span class="h-2.5 w-2.5 shrink-0 rounded-sm bg-current" aria-hidden="true"></span>
                <span class="text-[10px] font-black tracking-wide">{{ $style['label'] }}</span>
            </div>
        @endforeach
    </section>

    <section id="tambah-kalender" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
        <div class="min-w-0 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs sm:p-5 transition-colors">
            <div class="mb-4 flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div><h4 class="text-sm font-black text-slate-900 dark:text-slate-100">Tambah Kalender Global</h4><p class="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Berlaku untuk semua karyawan dan langsung memengaruhi jadwal efektif.</p></div>
            </div>
            <form action="{{ route('admin.work-calendar.store') }}" method="POST" class="grid min-w-0 gap-4 sm:grid-cols-2">
                @csrf
                <div class="w-full min-w-0 max-w-full">
                    <label for="calendar_date" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Tanggal *</label>
                    <x-date-input name="date" id="calendar_date" value="{{ old('date', $today) }}" required />
                </div>
                <div class="min-w-0">
                    <label for="calendar_type" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Jenis *</label>
                    <select id="calendar_type" name="type" required class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-200 ui-input ui-select">
                        <option value="public_holiday" @selected(old('type') === 'public_holiday')>Libur Nasional</option>
                        <option value="company_holiday" @selected(old('type', 'company_holiday') === 'company_holiday')>Libur Perusahaan</option>
                        <option value="special_working_day" @selected(old('type') === 'special_working_day')>Hari Kerja Khusus</option>
                    </select>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <label for="calendar_name" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Nama *</label>
                    <input id="calendar_name" name="name" value="{{ old('name') }}" maxlength="150" required placeholder="Contoh: Hari Kemerdekaan" class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3.5 text-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-200 ui-input">
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <label for="calendar_description" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Keterangan</label>
                    <textarea id="calendar_description" name="description" rows="2" maxlength="1000" class="w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3.5 py-3 text-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-200 ui-input" placeholder="Informasi operasional bila diperlukan">{{ old('description') }}</textarea>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <label for="calendar_audit_reason" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Alasan Perubahan *</label>
                    <textarea id="calendar_audit_reason" name="audit_reason" rows="2" minlength="5" maxlength="1000" required class="w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3.5 py-3 text-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-200 ui-input" placeholder="Minimal 5 karakter untuk audit trail">{{ old('audit_reason') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <p class="mb-3 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-[11px] leading-4 text-emerald-900 dark:text-emerald-300"><strong>Hari kerja khusus:</strong> menggunakan shift WORK reguler yang sudah ada. Jika karyawan belum memiliki shift, buat override Masuk Kerja di panel sebelah.</p>
                    <button type="submit" class="min-h-[44px] w-full rounded-xl bg-rose-600 px-5 text-xs font-black text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto ui-btn ui-btn-primary">Simpan Kalender</button>
                </div>
            </form>
        </div>

        <div class="min-w-0 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-900 dark:bg-slate-950 p-4 text-white shadow-xs sm:p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-rose-300">Prioritas tertinggi</p>
            <h4 class="mt-1 text-sm font-black">Buat Jadwal Khusus Karyawan</h4>
            <p class="mt-1 text-[11px] leading-4 text-slate-300">Perubahan ini mengirim notifikasi langsung ke karyawan.</p>
            <form id="override-create-form" action="{{ route('admin.schedule-overrides.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="override_employee_id" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-300">Karyawan *</label>
                    <select id="override_employee_id" name="employee_id" required class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-600 bg-slate-800 px-3 text-xs text-white focus:border-rose-400 focus:ring-2 focus:ring-rose-400/30">
                        <option value="">Pilih karyawan</option>
                        @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }} · {{ $employee->employee_code }}</option>@endforeach
                    </select>
                </div>
                <div class="w-full min-w-0 max-w-full [&_.ios-date-field]:!border-slate-600 [&_.ios-date-field]:!bg-slate-800 [&_.ios-date-field]:!text-white">
                    <label for="override_date" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-300">Tanggal *</label>
                    <x-date-input name="date" id="override_date" value="{{ old('date', $today) }}" required />
                </div>
                <div id="override-context" class="rounded-xl border border-slate-600 bg-slate-800/80 p-3" role="status" aria-live="polite">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Jadwal saat ini</p>
                    <div id="override-context-body" class="mt-2 text-[11px] leading-5 text-slate-300">Pilih karyawan dan tanggal untuk melihat jadwal reguler serta hasil efektif.</div>
                </div>
                <fieldset>
                    <legend class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-300">Override *</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-3 text-xs font-bold has-[:checked]:border-rose-400 has-[:checked]:bg-rose-500/15"><input type="radio" name="override_type" value="off" x-model="overrideType" @checked(old('override_type', 'off') === 'off')> Libur</label>
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-3 text-xs font-bold has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-500/15"><input type="radio" name="override_type" value="work" x-model="overrideType" @checked(old('override_type') === 'work')> Masuk Kerja</label>
                    </div>
                </fieldset>
                <div x-show="overrideType === 'work'" x-cloak>
                    <label for="override_shift_id" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-300">Shift *</label>
                    <select id="override_shift_id" name="shift_id" :required="overrideType === 'work'" class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-600 bg-slate-800 px-3 text-xs text-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/30">
                        <option value="">Pilih shift aktif</option>
                        @foreach($activeShifts as $shift)<option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->name }} · {{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}</option>@endforeach
                    </select>
                </div>
                <div x-show="overrideType === 'work'" x-cloak>
                    <label for="override_work_outlet_id" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-300">Outlet Kerja *</label>
                    <select id="override_work_outlet_id" name="work_outlet_id" :required="overrideType === 'work'" class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-600 bg-slate-800 px-3 text-xs text-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/30">
                        <option value="">Pilih Outlet Kerja</option>
                        @foreach($workOutlets as $outlet)<option value="{{ $outlet->id }}" @selected(old('work_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="override_reason" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-300">Alasan *</label>
                    <textarea id="override_reason" name="reason" rows="2" minlength="5" maxlength="1000" required class="w-full min-w-0 rounded-xl border border-slate-600 bg-slate-800 px-3.5 py-3 text-xs text-white placeholder:text-slate-500 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/30" placeholder="Minimal 5 karakter">{{ old('reason') }}</textarea>
                </div>
                <button id="override-submit" type="submit" disabled class="min-h-[44px] w-full rounded-xl bg-rose-600 px-5 text-xs font-black text-white transition hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 focus:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-50 ui-btn ui-btn-primary">Simpan Jadwal Khusus</button>
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs sm:p-5 transition-colors">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><h4 class="text-sm font-black text-slate-900 dark:text-slate-100">Tanggal Kalender</h4><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Perubahan global tampil otomatis di dashboard; sistem tidak mengirim notifikasi massal.</p></div>
            <form action="{{ route('admin.work-calendar.index') }}" method="GET" class="grid w-full min-w-0 gap-2 sm:w-auto sm:grid-cols-[170px_180px_auto]">
                <div class="w-full min-w-0 max-w-full"><x-date-input name="date" id="filter_calendar_date" value="{{ request('date') }}" placeholder="Filter tanggal" /></div>
                <select name="type" class="min-h-[44px] w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select"><option value="">Semua jenis</option>@foreach($typeStyles as $value => $style)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $style['label'] }}</option>@endforeach</select>
                <button class="min-h-[44px] rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 text-xs font-black text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Filter</button>
            </form>
        </div>

        @if($calendarDays->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-slate-300 dark:border-slate-800 px-4 py-10 text-center"><p class="text-xs font-black text-slate-700 dark:text-slate-300">Belum ada tanggal kalender</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Tambahkan hari libur atau hari kerja khusus dari formulir di atas.</p></div>
        @else
            <div class="mt-4 hidden overflow-x-auto lg:block ui-table-container">
                <table class="w-full min-w-[820px] text-left text-xs border-collapse ui-table">
                    <thead><tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400"><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Jenis</th><th class="px-3 py-3">Nama</th><th class="px-3 py-3">Dibuat oleh</th><th class="px-3 py-3 text-right">Kelola</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($calendarDays as $day)
                        @php($style = $typeStyles[$day->type] ?? ['label' => strtoupper($day->type), 'class' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'])
                        <tr class="align-top hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-3 py-4 font-black text-slate-900 dark:text-slate-100">{{ $day->date->locale('id')->isoFormat('ddd, D MMM YYYY') }}</td>
                            <td class="px-3 py-4"><span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black {{ $style['class'] }}">{{ $style['label'] }}</span></td>
                            <td class="px-3 py-4"><p class="font-bold text-slate-900 dark:text-slate-100">{{ $day->name }}</p><p class="mt-0.5 max-w-sm text-[11px] leading-4 text-slate-500 dark:text-slate-400">{{ $day->description ?: 'Tanpa keterangan.' }}</p></td>
                            <td class="px-3 py-4 text-[11px] text-slate-600 dark:text-slate-400">{{ $day->creator?->name ?? 'Sistem' }}</td>
                            <td class="px-3 py-4 text-right"><details class="inline-block text-left"><summary class="inline-flex min-h-[44px] cursor-pointer list-none items-center rounded-xl border border-slate-300 dark:border-slate-700 px-3 font-black text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Ubah</summary><div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4"><div class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 text-left shadow-xl"><div class="mb-3 flex items-center justify-between"><p class="text-xs font-black text-slate-900 dark:text-slate-100">Ubah Kalender</p><button type="button" onclick="this.closest('details').removeAttribute('open')" class="min-h-[44px] min-w-[44px] text-lg font-bold text-slate-500 dark:text-slate-400" aria-label="Tutup">&times;</button></div><form action="{{ route('admin.work-calendar.update', $day) }}" method="POST" class="space-y-3">@csrf @method('PUT')
                                <div class="w-full min-w-0 max-w-full"><label class="mb-1 block text-[10px] font-black uppercase text-slate-600 dark:text-slate-400">Tanggal</label><x-date-input name="date" id="edit_calendar_date_{{ $day->id }}" value="{{ $day->date->format('Y-m-d') }}" required /></div>
                                <select name="type" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select">@foreach($typeStyles as $value => $option)<option value="{{ $value }}" @selected($day->type === $value)>{{ $option['label'] }}</option>@endforeach</select>
                                <input name="name" value="{{ $day->name }}" required maxlength="150" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs">
                                <textarea name="description" rows="2" maxlength="1000" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 py-2 text-xs">{{ $day->description }}</textarea>
                                <textarea name="audit_reason" rows="2" required minlength="5" maxlength="1000" placeholder="Alasan perubahan (wajib)" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 py-2 text-xs"></textarea>
                                <button class="min-h-[44px] w-full rounded-xl bg-rose-600 px-4 text-xs font-black text-white ui-btn ui-btn-primary">Simpan Perubahan</button>
                            </form><form action="{{ route('admin.work-calendar.destroy', $day) }}" method="POST" class="mt-3 border-t border-slate-100 dark:border-slate-800 pt-3" onsubmit="return confirm('Hapus tanggal kalender ini? Histori absensi tetap disimpan.')">@csrf @method('DELETE')<label class="mb-1 block text-[10px] font-black uppercase text-slate-600 dark:text-slate-400">Alasan hapus</label><textarea name="reason" required minlength="5" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 py-2 text-xs"></textarea><button class="mt-2 min-h-[44px] w-full rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 px-4 text-xs font-black text-rose-700 dark:text-rose-300">Hapus — histori tetap aman</button></form></div></div></details></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 space-y-3 lg:hidden">
                @foreach($calendarDays as $day)
                    @php($style = $typeStyles[$day->type] ?? ['label' => strtoupper($day->type), 'class' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'])
                    <article class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50 dark:bg-slate-800/60">
                        <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $day->date->locale('id')->isoFormat('dddd, D MMM YYYY') }}</p><p class="mt-1 font-bold text-slate-700 dark:text-slate-300">{{ $day->name }}</p></div><span class="rounded-lg border px-2 py-1 text-[9px] font-black {{ $style['class'] }}">{{ $style['label'] }}</span></div>
                        @if($day->description)<p class="mt-2 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{{ $day->description }}</p>@endif
                        <details class="mt-3"><summary class="flex min-h-[44px] cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-black text-slate-700 dark:text-slate-300">Ubah / Hapus</summary><div class="mt-3 rounded-xl bg-white dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-800"><form action="{{ route('admin.work-calendar.update', $day) }}" method="POST" class="space-y-3">@csrf @method('PUT')<div class="w-full min-w-0 max-w-full"><x-date-input name="date" id="mobile_edit_calendar_date_{{ $day->id }}" value="{{ $day->date->format('Y-m-d') }}" required /></div><select name="type" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select">@foreach($typeStyles as $value => $option)<option value="{{ $value }}" @selected($day->type === $value)>{{ $option['label'] }}</option>@endforeach</select><input name="name" value="{{ $day->name }}" required class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs"><textarea name="description" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 py-2 text-xs">{{ $day->description }}</textarea><textarea name="audit_reason" required minlength="5" rows="2" placeholder="Alasan perubahan (wajib)" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 py-2 text-xs"></textarea><button class="min-h-[44px] w-full rounded-xl bg-rose-600 text-xs font-black text-white ui-btn ui-btn-primary">Simpan Perubahan</button></form><form action="{{ route('admin.work-calendar.destroy', $day) }}" method="POST" class="mt-3 border-t border-slate-200 dark:border-slate-800 pt-3" onsubmit="return confirm('Hapus kalender? Histori absensi tetap aman.')">@csrf @method('DELETE')<textarea name="reason" required minlength="5" rows="2" placeholder="Alasan hapus" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 py-2 text-xs"></textarea><button class="mt-2 min-h-[44px] w-full rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-xs font-black text-rose-700 dark:text-rose-300">Hapus Kalender</button></form></div></details>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $calendarDays->links() }}</div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs sm:p-5 transition-colors">
        <div><h4 class="text-sm font-black text-slate-900 dark:text-slate-100">Jadwal Khusus Aktif</h4><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Override menang atas kalender global dan jadwal reguler. Penghapusan mengembalikan hasil ke sumber berikutnya.</p></div>
        @if($overrides->isEmpty())
            <div class="mt-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-800 px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">Belum ada jadwal khusus karyawan.</div>
        @else
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach($overrides as $override)
                    <article class="min-w-0 rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-800/50">
                        <div class="flex flex-wrap items-start justify-between gap-2"><div class="min-w-0"><p class="truncate text-xs font-black text-slate-900 dark:text-slate-100">{{ $override->employee?->full_name }}</p><p class="mt-0.5 text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ $override->date->format('d/m/Y') }} · {{ $override->employee?->employee_code }}</p></div><span class="rounded-lg border px-2 py-1 text-[9px] font-black {{ $override->override_type === 'work' ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300' : 'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $override->override_type === 'work' ? 'JADWAL KHUSUS' : 'LIBUR KHUSUS' }}</span></div>
                        <p class="mt-3 text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ $override->override_type === 'work' ? ($override->shift?->name.' · '.substr($override->shift?->start_time ?? '', 0, 5).'–'.substr($override->shift?->end_time ?? '', 0, 5)) : 'Tidak ada kewajiban check-in' }}</p>
                        @if($override->override_type === 'work')<p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Outlet Kerja: {{ $override->workOutlet?->name ?? $override->employee?->outlet?->name ?? '-' }}</p>@endif
                        <p class="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{{ $override->reason }}</p>
                        <details class="mt-3"><summary class="flex min-h-[44px] cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-black text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Kelola Jadwal Khusus</summary><div class="mt-3 rounded-xl bg-white dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-800" x-data="{ type: '{{ $override->override_type }}' }"><form action="{{ route('admin.schedule-overrides.update', $override) }}" method="POST" class="space-y-3">@csrf @method('PUT')<select name="employee_id" required class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select">@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected($override->employee_id === $employee->id)>{{ $employee->full_name }}</option>@endforeach</select><div class="w-full min-w-0 max-w-full"><x-date-input name="date" id="override_edit_date_{{ $override->id }}" value="{{ $override->date->format('Y-m-d') }}" required /></div><select name="override_type" x-model="type" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select"><option value="off">Libur</option><option value="work">Masuk Kerja</option></select><select x-show="type === 'work'" :required="type === 'work'" name="shift_id" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select"><option value="">Pilih shift</option>@foreach($activeShifts as $shift)<option value="{{ $shift->id }}" @selected($override->shift_id === $shift->id)>{{ $shift->name }}</option>@endforeach</select><select x-show="type === 'work'" :required="type === 'work'" name="work_outlet_id" class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 text-xs ui-select">@foreach($workOutlets as $workOutlet)<option value="{{ $workOutlet->id }}" @selected(($override->work_outlet_id ?: $override->employee?->outlet_id) === $workOutlet->id)>{{ $workOutlet->name }}</option>@endforeach</select><textarea name="reason" required minlength="5" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3 py-2 text-xs">{{ $override->reason }}</textarea><button class="min-h-[44px] w-full rounded-xl bg-rose-600 text-xs font-black text-white ui-btn ui-btn-primary">Simpan & Beri Tahu</button></form><form action="{{ route('admin.schedule-overrides.destroy', $override) }}" method="POST" class="mt-3 border-t border-slate-200 dark:border-slate-800 pt-3" onsubmit="return confirm('Hapus jadwal khusus ini?')">@csrf @method('DELETE')<textarea name="reason" required minlength="5" rows="2" placeholder="Alasan penghapusan" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 py-2 text-xs"></textarea><button class="mt-2 min-h-[44px] w-full rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-xs font-black text-rose-700 dark:text-rose-300">Hapus Jadwal Khusus</button></form></div></details>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <aside class="rounded-xl border border-sky-200 dark:border-sky-900/60 bg-sky-50 dark:bg-sky-950/40 p-4 text-[11px] leading-5 text-sky-900 dark:text-sky-300"><strong>Keamanan histori:</strong> mengubah atau menghapus kalender dan override tidak menghapus record absensi. Semua aksi tersimpan di Audit Trail. Kalender global tidak mengirim notifikasi massal; perubahan override dikirim hanya kepada karyawan terkait.</aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const employee = document.getElementById('override_employee_id');
    const date = document.getElementById('override_date');
    const context = document.getElementById('override-context');
    const body = document.getElementById('override-context-body');
    const submit = document.getElementById('override-submit');
    let previewRequest = null;

    if (!employee || !date || !context || !body || !submit) return;

    const setState = (state, lines) => {
        context.className = 'rounded-xl border p-3 ' + ({
            ready: 'border-emerald-500/50 bg-emerald-500/10',
            loading: 'border-slate-600 bg-slate-800/80',
            error: 'border-rose-500/60 bg-rose-500/10',
            empty: 'border-slate-600 bg-slate-800/80',
        }[state] || 'border-slate-600 bg-slate-800/80');
        body.replaceChildren(...lines.map((line, index) => {
            const paragraph = document.createElement('p');
            paragraph.className = index === 0 ? 'font-bold text-white' : 'text-slate-300';
            paragraph.textContent = line;
            return paragraph;
        }));
    };

    const refreshPreview = async () => {
        submit.disabled = true;
        previewRequest?.abort();

        if (!employee.value || !date.value) {
            setState('empty', ['Pilih karyawan dan tanggal untuk melihat jadwal reguler serta hasil efektif.']);
            return;
        }

        previewRequest = new AbortController();
        setState('loading', ['Memuat jadwal saat ini…']);

        const url = new URL(@json(route('admin.work-calendar.effective-preview')), window.location.origin);
        url.searchParams.set('employee_id', employee.value);
        url.searchParams.set('date', date.value);

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: previewRequest.signal,
            });
            if (!response.ok) throw new Error('Preview tidak tersedia');

            const data = await response.json();
            const regular = data.regular
                ? `${String(data.regular.type).toUpperCase()}${data.regular.shift ? ' · ' + data.regular.shift : ''}${data.regular.hours ? ' (' + data.regular.hours + ')' : ''}`
                : 'Belum ada jadwal reguler';
            const effective = `${data.label}${data.holiday_name ? ' · ' + data.holiday_name : ''}${data.effective_shift ? ' · ' + data.effective_shift.name + (data.effective_shift.hours ? ' (' + data.effective_shift.hours + ')' : '') : ''}`;

            setState('ready', [
                `Reguler: ${regular}`,
                `Efektif saat ini: ${effective}`,
                data.is_working_day ? 'Status: hari kerja wajib' : 'Status: tidak ada kewajiban presensi reguler',
            ]);
            submit.disabled = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            setState('error', ['Jadwal saat ini gagal dimuat.', 'Periksa pilihan atau koneksi, lalu coba lagi.']);
        }
    };

    employee.addEventListener('change', refreshPreview);
    date.addEventListener('change', refreshPreview);
    refreshPreview();
});
</script>
@endsection
