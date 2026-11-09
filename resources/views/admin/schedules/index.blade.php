@extends('layouts.admin')

@section('title', 'Jadwal Mingguan')
@section('page-title', 'Jadwal & Kalender')

@section('content')
<div class="space-y-6">

    <nav class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-200/70 dark:bg-slate-800/70 p-1" aria-label="Navigasi penjadwalan">
        <span class="min-h-[44px] shrink-0 rounded-lg bg-white dark:bg-slate-900 px-4 py-3 text-center text-xs font-bold text-teal-700 dark:text-teal-400 shadow-xs" aria-current="page">Jadwal Mingguan</span>
        <a href="{{ route('admin.work-calendar.index') }}" class="min-h-[44px] shrink-0 rounded-lg px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 transition hover:bg-white dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100">Kalender Kerja</a>
    </nav>

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

    <!-- Header Actions & Week Navigation Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs p-4 flex flex-col xl:flex-row xl:items-center justify-between gap-4 transition-colors">
        <!-- Date Range Navigation -->
        <div class="flex min-w-0 flex-wrap items-center gap-2 sm:gap-3">
            <a href="{{ route('admin.schedules.index', ['start_date' => $prevWeekDate]) }}" class="min-h-[44px] px-3.5 py-2 border border-slate-200 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center gap-1.5">
                &larr; Minggu Lalu
            </a>

            <div class="text-center px-1">
                <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100 block">
                    {{ $startDate->locale('id')->isoFormat('D MMMM YYYY') }} — {{ $endDate->locale('id')->isoFormat('D MMMM YYYY') }}
                </span>
                <span class="text-[10px] text-teal-600 dark:text-teal-400 font-bold uppercase tracking-wider">Minggu ke-{{ $startDate->weekOfYear }}</span>
            </div>

            <a href="{{ route('admin.schedules.index', ['start_date' => $nextWeekDate]) }}" class="min-h-[44px] px-3.5 py-2 border border-slate-200 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center gap-1.5">
                Minggu Depan &rarr;
            </a>

            @if(!$startDate->isSameWeek(now()))
                <a href="{{ route('admin.schedules.index') }}" class="min-h-[44px] inline-flex items-center text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline px-2">Hari Ini</a>
            @endif
        </div>

        <!-- Right Action Buttons & Outlet Filter -->
        <div class="flex flex-wrap items-center gap-2">
            <x-outlet-filter />
            <a href="{{ route('admin.work-calendar.index') }}#tambah-kalender" class="min-h-[44px] px-3.5 py-2 border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">Override / Libur</a>
            <a href="{{ route('admin.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'show_copy_preview' => 1]) }}" class="min-h-[44px] px-3.5 py-2 border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                <span>Salin Minggu Lalu</span>
            </a>

            <button type="button" onclick="openAssignModal('', '{{ date('Y-m-d') }}')" class="min-h-[44px] px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Atur Jadwal</span>
            </button>
        </div>
    </div>

    <!-- Copy Week Preview Warning Alert -->
    @if($copyPreview)
        <div class="bg-indigo-50/80 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/60 rounded-2xl p-5 space-y-3 text-xs">
            <div class="flex items-center justify-between font-bold text-indigo-900 dark:text-indigo-200">
                <span>Pratinjau Salin Jadwal Minggu Lalu</span>
                <a href="{{ route('admin.schedules.index', ['start_date' => $startDate->format('Y-m-d')]) }}" class="text-teal-600 dark:text-teal-400 text-xs font-semibold hover:underline">Tutup Pratinjau</a>
            </div>

            <p class="text-indigo-800 dark:text-indigo-300">
                Akan menyalin <strong>{{ $copyPreview['total_source_items'] }}</strong> item jadwal dari minggu ({{ $copyPreview['prev_start'] }} s.d. {{ $copyPreview['prev_end'] }}).
                @if($copyPreview['conflict_count'] > 0)
                    <span class="text-rose-700 dark:text-rose-400 font-bold block mt-1">⚠ Peringatan: Terdapat {{ $copyPreview['conflict_count'] }} jadwal yang sudah ada pada minggu ini (Bentrokan).</span>
                @endif
            </p>

            <form action="{{ route('admin.schedules.copy-week.execute') }}" method="POST" class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                @csrf
                <input type="hidden" name="target_start_date" value="{{ $startDate->format('Y-m-d') }}">
                
                <label class="flex items-center gap-2 text-indigo-900 dark:text-indigo-200 font-semibold cursor-pointer">
                    <input type="checkbox" name="overwrite" value="1" class="h-4 w-4 rounded text-teal-600 focus:ring-teal-500">
                    <span>Timpa Jadwal Existing yang Bentrok</span>
                </label>

                <button type="submit" class="w-full sm:w-auto min-h-[42px] px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer">
                    Eksekusi Salin Jadwal
                </button>
            </form>
        </div>
    @endif

    <!-- Main Schedule Container -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs p-5 space-y-4 transition-colors">

        @if($employees->isEmpty())
            <!-- Clean Empty State -->
            <div class="text-center py-14 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-800/30">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center mx-auto mb-3 shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Karyawan Aktif</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
                    Silakan tambahkan data karyawan terlebih dahulu di menu Manajemen Karyawan untuk membuat jadwal kerja.
                </p>
            </div>
        @else
            <!-- Desktop Weekly Matrix Grid (Hidden on Mobile) -->
            <div class="hidden md:block overflow-x-auto ui-table-container">
                <table class="w-full text-left text-xs border-collapse ui-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider bg-slate-50/60 dark:bg-slate-800/60">
                            <th class="p-3.5 w-48 min-w-[180px]">Karyawan</th>
                            @foreach($weekDays as $day)
                                <th class="p-3.5 text-center min-w-[110px] {{ $day['is_today'] ? 'bg-teal-50/60 dark:bg-teal-950/40 text-teal-800 dark:text-teal-300 border-x border-teal-200/80 dark:border-teal-900/60' : '' }}">
                                    <div>{{ $day['day_name'] }}</div>
                                    <div class="text-[11px] font-mono font-normal">{{ $day['short_date'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($employees as $emp)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Employee Info Column -->
                                <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    <div class="truncate text-sm">{{ $emp->full_name }}</div>
                                    <div class="text-[10px] font-mono text-teal-600 dark:text-teal-400 font-normal mt-0.5">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?: 'No Job' }}</div>
                                </td>

                                <!-- 7 Days Columns -->
                                @foreach($weekDays as $day)
                                    @php
                                        $key = $emp->id . '_' . $day['date'];
                                        $sch = $scheduleMatrix[$key] ?? null;
                                        $effective = $effectiveScheduleMatrix[$key] ?? null;
                                    @endphp
                                    <td class="p-2 text-center align-middle {{ $day['is_today'] ? 'bg-teal-50/30 dark:bg-teal-950/20 border-x border-teal-100 dark:border-teal-900/40' : '' }}">
                                        @if($effective && $effective['source'] === 'employee_override')
                                            <a href="{{ route('admin.work-calendar.index') }}" class="block rounded-xl border p-2 text-[10px] font-black transition hover:shadow-xs {{ $effective['is_working_day'] ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-300' : 'border-violet-200 dark:border-violet-800/60 bg-violet-50 dark:bg-violet-950/60 text-violet-900 dark:text-violet-300' }}">
                                                <span class="block">{{ $effective['is_working_day'] ? ($effective['shift']?->code ?? 'WORK') : 'LIBUR KHUSUS' }}</span>
                                                <span class="mt-1 block text-[8px] tracking-wide opacity-70">OVERRIDE</span>
                                            </a>
                                        @elseif($effective && in_array($effective['source'], ['public_holiday', 'company_holiday'], true))
                                            <a href="{{ route('admin.work-calendar.index', ['date' => $day['date']]) }}" class="block rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 p-2 text-[10px] font-black text-amber-900 dark:text-amber-300 transition hover:bg-amber-100 dark:hover:bg-amber-900/80">
                                                <span class="block">LIBUR</span><span class="mt-1 block truncate text-[8px] opacity-75">{{ $effective['holiday_name'] }}</span>
                                            </a>
                                        @elseif($effective && $effective['source'] === 'special_working_day')
                                            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 p-2 text-emerald-900 dark:text-emerald-300">
                                                <span class="block text-[10px] font-black">{{ $effective['shift']?->code ?? 'SHIFT?' }}</span><span class="mt-1 block text-[8px] font-black">KERJA KHUSUS</span>
                                                @if($sch)<button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '{{ $sch->shift_id }}', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')" class="mt-1 min-h-[24px] text-[8px] font-bold underline">Ubah reguler</button>@endif
                                            </div>
                                        @elseif($sch)
                                            @if($sch->schedule_type === 'work' && $sch->shift)
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '{{ $sch->shift_id }}', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                     class="p-2 rounded-xl border border-teal-200 dark:border-teal-900/60 bg-teal-50/70 dark:bg-teal-950/40 hover:bg-teal-100 dark:hover:bg-teal-900/60 hover:border-teal-300 dark:hover:border-teal-800 hover:shadow-xs text-teal-900 dark:text-teal-200 transition-all cursor-pointer space-y-0.5 group">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold font-mono text-[11px] uppercase block">{{ $sch->shift->code }}</span>
                                                        <svg class="w-3 h-3 text-teal-500 dark:text-teal-400 group-hover:text-teal-700 dark:group-hover:text-teal-300 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </div>
                                                    <span class="text-[10px] block text-slate-600 dark:text-slate-400 font-medium">{{ substr($sch->shift->start_time, 0, 5) }} - {{ substr($sch->shift->end_time, 0, 5) }}</span>
                                                </div>
                                            @elseif($sch->schedule_type === 'off')
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                     class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200/70 dark:hover:bg-slate-700/70 hover:border-slate-300 hover:shadow-xs text-slate-700 dark:text-slate-300 font-extrabold text-[11px] transition-all cursor-pointer flex items-center justify-between group">
                                                    <span>OFF / LIBUR</span>
                                                    <svg class="w-3 h-3 text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </div>
                                            @elseif($sch->schedule_type === 'holiday')
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                     class="p-2 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 hover:border-amber-300 hover:shadow-xs text-amber-900 dark:text-amber-300 font-extrabold text-[11px] transition-all cursor-pointer flex items-center justify-between group">
                                                    <span>HOLIDAY</span>
                                                    <svg class="w-3 h-3 text-amber-500 dark:text-amber-400 group-hover:text-amber-700 dark:group-hover:text-amber-300 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </div>
                                            @endif
                                        @else
                                            <button type="button" onclick="openAssignModal('{{ $emp->id }}', '{{ $day['date'] }}')" class="min-h-[44px] w-full py-2 border border-dashed border-slate-200 dark:border-slate-800 hover:border-teal-400 dark:hover:border-teal-700 hover:bg-teal-50/40 dark:hover:bg-teal-950/20 rounded-xl text-[11px] text-slate-400 dark:text-slate-500 font-medium transition-colors cursor-pointer">
                                                + Set
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Day-by-Day List View (Visible on Mobile) -->
            <div class="md:hidden space-y-4">
                @foreach($weekDays as $day)
                    <div class="bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-700 pb-2.5">
                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                                {{ $day['day_name'] }}, {{ $day['short_date'] }}
                            </span>
                            @if($day['is_today'])
                                <span class="px-2.5 py-0.5 bg-teal-600 text-white font-bold text-[10px] rounded-full">HARI INI</span>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @foreach($employees as $emp)
                                @php
                                    $key = $emp->id . '_' . $day['date'];
                                    $sch = $scheduleMatrix[$key] ?? null;
                                    $effective = $effectiveScheduleMatrix[$key] ?? null;
                                @endphp
                                <div class="flex min-w-0 flex-col gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs sm:flex-row sm:items-center sm:justify-between shadow-xs">
                                    <div class="min-w-0">
                                        <h5 class="font-bold text-slate-900 dark:text-slate-100">{{ $emp->full_name }}</h5>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ $emp->employee_code }}</span>
                                    </div>

                                    <div class="min-w-0 sm:text-right">
                                        @if($effective && $effective['source'] === 'employee_override')
                                            <a href="{{ route('admin.work-calendar.index') }}" class="inline-flex min-h-[44px] max-w-full items-center rounded-xl border px-3 text-[10px] font-black {{ $effective['is_working_day'] ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300' : 'border-violet-200 dark:border-violet-800/60 bg-violet-50 dark:bg-violet-950/60 text-violet-800 dark:text-violet-300' }}">{{ $effective['is_working_day'] ? 'JADWAL KHUSUS · '.($effective['shift']?->code ?? 'WORK') : 'LIBUR KHUSUS' }}</a>
                                        @elseif($effective && in_array($effective['source'], ['public_holiday', 'company_holiday'], true))
                                            <a href="{{ route('admin.work-calendar.index', ['date' => $day['date']]) }}" class="inline-flex min-h-[44px] max-w-full items-center rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 px-3 text-left text-[10px] font-black text-amber-900 dark:text-amber-300">LIBUR · {{ $effective['holiday_name'] }}</a>
                                        @elseif($effective && $effective['source'] === 'special_working_day')
                                            <span class="inline-flex min-h-[44px] max-w-full items-center rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 px-3 text-[10px] font-black text-emerald-900 dark:text-emerald-300">KERJA KHUSUS · {{ $effective['shift']?->code ?? 'SHIFT BELUM ADA' }}</span>
                                        @elseif($sch)
                                            @if($sch->schedule_type === 'work' && $sch->shift)
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '{{ $sch->shift_id }}', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                        class="min-h-[44px] px-3 py-1 bg-teal-50 dark:bg-teal-950/40 text-teal-800 dark:text-teal-300 border border-teal-200/80 dark:border-teal-900/60 font-mono font-bold text-xs rounded-xl active:scale-95 transition-transform cursor-pointer">
                                                    {{ $sch->shift->code }} ({{ substr($sch->shift->start_time, 0, 5) }}-{{ substr($sch->shift->end_time, 0, 5) }})
                                                </button>
                                            @elseif($sch->schedule_type === 'off')
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                        class="min-h-[44px] px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold text-xs rounded-xl active:scale-95 transition-transform cursor-pointer">
                                                    OFF / LIBUR
                                                </button>
                                            @elseif($sch->schedule_type === 'holiday')
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}', '{{ $sch->work_outlet_id ?? $emp->outlet_id }}')"
                                                        class="min-h-[44px] px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-bold text-xs rounded-xl active:scale-95 transition-transform cursor-pointer">
                                                    HOLIDAY
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" onclick="openAssignModal('{{ $emp->id }}', '{{ $day['date'] }}')" class="min-h-[44px] inline-flex items-center text-teal-600 dark:text-teal-400 font-bold text-xs hover:underline">
                                                + Set Jadwal
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

<!-- Modal Form Assign / Edit Schedule -->
<div id="modal-assign-schedule" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="max-h-[calc(100dvh-2rem)] overflow-y-auto bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xl max-w-md w-full p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="modal_title" class="text-sm font-bold text-slate-900 dark:text-slate-100">Atur Jadwal Kerja Karyawan</h3>
            <button type="button" onclick="document.getElementById('modal-assign-schedule').classList.add('hidden')" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-xl">&times;</button>
        </div>

        <form id="schedule-form" action="{{ route('admin.schedules.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="form_method" value="POST">

            <div>
                <label for="modal_employee_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Karyawan *</label>
                <select name="employee_id" id="modal_employee_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" data-home-outlet-id="{{ $emp->outlet_id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full min-w-0 max-w-full">
                <label for="modal_work_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Tanggal *</label>
                <x-date-input name="work_date" id="modal_work_date" value="{{ date('Y-m-d') }}" required />
            </div>

            <div>
                <label for="modal_schedule_type" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Jenis Jadwal *</label>
                <select name="schedule_type" id="modal_schedule_type" required onchange="toggleShiftSelect(this.value)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                    <option value="work">Kerja (Masuk Shift)</option>
                    <option value="off">OFF / Libur Karyawan</option>
                    <option value="holiday">Holiday / Hari Libur Toko</option>
                </select>
            </div>

            <div id="container-shift-select">
                <label for="modal_shift_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Shift Kerja *</label>
                <select name="shift_id" id="modal_shift_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                    <option value="">-- Pilih Shift Kerja --</option>
                    @foreach($shifts as $sf)
                        <option value="{{ $sf->id }}">{{ $sf->name }} ({{ $sf->code }} • {{ substr($sf->start_time, 0, 5) }}-{{ substr($sf->end_time, 0, 5) }}) {{ !$sf->is_active ? '[NONAKTIF]' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div id="container-work-outlet-select">
                <label for="modal_work_outlet_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Outlet Kerja *</label>
                <select name="work_outlet_id" id="modal_work_outlet_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                    <option value="">-- Pilih Outlet Kerja --</option>
                    @foreach($workOutlets as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }} ({{ $outlet->code }})</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Home Outlet adalah outlet induk karyawan. Outlet Kerja adalah lokasi kerja pada tanggal ini.</p>
            </div>

            <div>
                <label for="modal_notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Catatan Opsional</label>
                <input type="text" name="notes" id="modal_notes" placeholder="Catatan instruksi shift" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition">
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-3">
                <button type="button" id="btn_delete_schedule" class="hidden min-h-[44px] px-3.5 py-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 font-bold text-xs rounded-xl transition-colors cursor-pointer" onclick="confirmDeleteSchedule()">
                    Hapus Jadwal
                </button>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="document.getElementById('modal-assign-schedule').classList.add('hidden')" class="min-h-[44px] px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors">Batal</button>
                    <button type="submit" id="btn_submit_schedule" class="min-h-[44px] px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan Jadwal
                    </button>
                </div>
            </div>
        </form>

        <!-- Hidden Form for Deletion -->
        <form id="delete-schedule-form" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
window.currentDeleteScheduleId = null;

function openAssignModal(employeeId, dateStr) {
    document.getElementById('schedule-form').action = "{{ route('admin.schedules.store') }}";
    document.getElementById('form_method').value = "POST";
    document.getElementById('modal_title').innerText = "Tambah Jadwal Kerja Karyawan";
    document.getElementById('modal_employee_id').value = employeeId || "";
    document.getElementById('modal_work_date').value = dateStr || "{{ date('Y-m-d') }}";
    document.getElementById('modal_schedule_type').value = "work";
    document.getElementById('modal_shift_id').value = "";
    setDefaultWorkOutlet();
    document.getElementById('modal_notes').value = "";
    document.getElementById('btn_delete_schedule').classList.add('hidden');
    document.getElementById('btn_submit_schedule').innerText = "Simpan Jadwal";
    toggleShiftSelect("work");
    document.getElementById('modal-assign-schedule').classList.remove('hidden');
}

function openEditModal(scheduleId, employeeId, dateStr, scheduleType, shiftId, notes, workOutletId = '') {
    document.getElementById('schedule-form').action = "/admin/schedules/" + scheduleId;
    document.getElementById('form_method').value = "PUT";
    document.getElementById('modal_title').innerText = "Ubah Jadwal Kerja Karyawan";
    document.getElementById('modal_employee_id').value = employeeId;
    document.getElementById('modal_work_date').value = dateStr;
    document.getElementById('modal_schedule_type').value = scheduleType;
    document.getElementById('modal_shift_id').value = shiftId || "";
    document.getElementById('modal_work_outlet_id').value = workOutletId || "";
    document.getElementById('modal_notes').value = notes || "";
    document.getElementById('btn_delete_schedule').classList.remove('hidden');
    document.getElementById('btn_submit_schedule').innerText = "Simpan Perubahan";
    window.currentDeleteScheduleId = scheduleId;
    toggleShiftSelect(scheduleType);
    document.getElementById('modal-assign-schedule').classList.remove('hidden');
}

function confirmDeleteSchedule() {
    if (!window.currentDeleteScheduleId) return;
    
    if (confirm("Apakah Anda yakin ingin menghapus jadwal kerja ini?")) {
        const deleteForm = document.getElementById('delete-schedule-form');
        deleteForm.action = "/admin/schedules/" + window.currentDeleteScheduleId;
        deleteForm.submit();
    }
}

function toggleShiftSelect(type) {
    const container = document.getElementById('container-shift-select');
    const shiftSelect = document.getElementById('modal_shift_id');
    const outletContainer = document.getElementById('container-work-outlet-select');
    const outletSelect = document.getElementById('modal_work_outlet_id');
    if (type === 'work') {
        container.classList.remove('hidden');
        shiftSelect.required = true;
        outletContainer.classList.remove('hidden');
        outletSelect.required = true;
    } else {
        container.classList.add('hidden');
        shiftSelect.required = false;
        outletContainer.classList.add('hidden');
        outletSelect.required = false;
    }
}

function setDefaultWorkOutlet() {
    const employeeSelect = document.getElementById('modal_employee_id');
    const workOutletSelect = document.getElementById('modal_work_outlet_id');
    const homeOutletId = employeeSelect.options[employeeSelect.selectedIndex]?.dataset.homeOutletId;
    if (homeOutletId && [...workOutletSelect.options].some((option) => option.value === homeOutletId)) {
        workOutletSelect.value = homeOutletId;
    }
}

document.getElementById('modal_employee_id').addEventListener('change', setDefaultWorkOutlet);
</script>
@endsection
