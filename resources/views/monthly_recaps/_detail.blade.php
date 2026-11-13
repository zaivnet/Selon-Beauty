@php
    $summary = $recap['summary'];
    $period = $recap['period'];
    $employee = $recap['employee'];
    $isEmployeeView = $isEmployeeView ?? false;
    $returnFilters = $returnFilters ?? [];
    $returnTransport = array_filter([
        'return_employee_id' => $returnFilters['employee_id'] ?? null,
        'return_job_title_id' => $returnFilters['job_title_id'] ?? null,
        'return_page' => $returnFilters['page'] ?? null,
    ]);
    $backQuery = ['year' => $period['year'], 'month' => $period['month'], ...array_filter($returnFilters)];
    $duration = static function (int $minutes): string {
        if ($minutes <= 0) return '0m';
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;
        return trim(($hours ? $hours.'j ' : '').($remainder ? $remainder.'m' : ''));
    };
    $time = static fn ($value): string => $value ? ($value instanceof \Carbon\CarbonInterface ? $value : \Carbon\Carbon::parse($value))->format('H:i') : '—';
    $prevRoute = $isEmployeeView
        ? route('employee.monthly-recap.show', $navigation['previous'])
        : route('admin.monthly-recaps.show', ['employee' => $employee->id, ...$navigation['previous'], ...$returnTransport]);
    $nextRoute = $isEmployeeView
        ? route('employee.monthly-recap.show', $navigation['next'])
        : route('admin.monthly-recaps.show', ['employee' => $employee->id, ...$navigation['next'], ...$returnTransport]);
    $printRoute = $isEmployeeView
        ? route('employee.monthly-recap.print', ['year' => $period['year'], 'month' => $period['month']])
        : route('admin.monthly-recaps.print', ['employee' => $employee->id, 'year' => $period['year'], 'month' => $period['month']]);
    $csvRoute = $isEmployeeView
        ? route('employee.monthly-recap.export-csv', ['year' => $period['year'], 'month' => $period['month']])
        : route('admin.monthly-recaps.export-detail', ['employee_id' => $employee->id, 'year' => $period['year'], 'month' => $period['month']]);
@endphp

<div class="space-y-4 md:space-y-5" x-data="{ isDownloadingCsv: false }">
    @if($isEmployeeView)
        <nav class="flex gap-1.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xs transition-colors" aria-label="Jadwal dan rekap">
            <a href="{{ route('employee.schedules.index') }}" class="flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-extrabold text-slate-500 dark:text-slate-400 transition hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Jadwal</span>
            </a>
            <a href="{{ route('employee.monthly-recap.show', ['year' => $period['year'], 'month' => $period['month']]) }}" aria-current="page" class="flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-xl bg-teal-700 dark:bg-teal-600 px-3 text-xs font-black text-white shadow-xs transition-all ui-btn ui-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Rekap Saya</span>
            </a>
        </nav>
    @endif

    <!-- Employee & Period Header Card -->
    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="p-5 md:flex md:items-start md:justify-between md:gap-6 md:p-6">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-lg border px-2.5 py-1 text-[10px] font-black tracking-wide uppercase {{ $summary['readiness_status'] === 'READY' ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300' }}">
                        {{ $summary['readiness_label'] }}
                    </span>
                    @if(isset($attendancePeriod) && $attendancePeriod->isClosed())
                        <span class="rounded-lg bg-indigo-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-2xs">
                            PERIODE TERKUNCI
                        </span>
                    @else
                        <span class="rounded-lg bg-emerald-100 dark:bg-emerald-950/60 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800/60">
                            PERIODE TERBUKA
                        </span>
                    @endif
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                        {{ $period['label'] }}
                    </span>
                </div>
                <h2 class="mt-2 truncate text-xl font-black tracking-tight text-slate-950 dark:text-slate-100 md:text-2xl">{{ $employee->full_name }}</h2>
                <p class="truncate text-xs font-bold text-slate-500 dark:text-slate-400">{{ $employee->employee_code }} · {{ $employee->jobTitle?->name ?? 'Karyawan' }}</p>
                <p class="mt-2 max-w-xl text-[11px] font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                    Rekap kehadiran ini selalu mengikuti data terkini dan bukan perhitungan nominal gaji.
                </p>
            </div>

            <!-- Action Buttons Grid -->
            <div class="mt-4 grid grid-cols-2 gap-2 sm:flex sm:items-center md:mt-0 md:shrink-0">
                @unless($isEmployeeView)
                    <a href="{{ route('admin.monthly-recaps.index', $backQuery) }}" class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-3.5 text-xs font-black text-slate-700 dark:text-slate-200 transition hover:bg-slate-200 dark:hover:bg-slate-700 shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Kembali</span>
                    </a>
                @endunless

                <a href="{{ $printRoute }}" target="_blank" class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 text-xs font-black text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak</span>
                </a>

                <a href="{{ $csvRoute }}"
                   @click="isDownloadingCsv = true; setTimeout(() => isDownloadingCsv = false, 4000)"
                   class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 text-xs font-black text-white shadow-xs transition hover:bg-emerald-700">
                    <template x-if="!isDownloadingCsv">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </template>
                    <template x-if="isDownloadingCsv">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                    <span x-text="isDownloadingCsv ? 'Mengunduh...' : 'CSV Detail'">CSV Detail</span>
                </a>

                @if(!$isEmployeeView && in_array(Auth::user()->role, ['owner', 'superadmin'], true))
                    <a href="{{ route('admin.audit-logs.index', ['employee_id' => $employee->id, 'module' => 'attendance']) }}" class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs font-black text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Audit Trail</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Month Navigation Bar -->
        <div class="grid grid-cols-2 divide-x divide-slate-200 dark:divide-slate-800 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40">
            <a href="{{ $prevRoute }}" class="flex min-h-[48px] items-center justify-center gap-1.5 text-xs font-black text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Bulan sebelumnya</span>
            </a>
            <a href="{{ $nextRoute }}" class="flex min-h-[48px] items-center justify-center gap-1.5 text-xs font-black text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800">
                <span>Bulan berikutnya</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </section>

    <!-- Review Notice or Success Card -->
    @if($summary['review_required_count'] > 0)
        <section class="rounded-2xl border border-amber-300 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/30 p-4 transition-colors" role="alert">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99L13.74 4a2 2 0 00-3.48 0L3.33 16.01A2 2 0 005.07 19z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-black text-amber-950 dark:text-amber-100">Data belum siap digunakan</h3>
                    <p class="mt-1 text-[11px] font-medium leading-relaxed text-amber-900 dark:text-amber-200">
                        Ada {{ $summary['review_required_count'] }} tanggal yang perlu diperiksa. Buka kartu atau baris bertanda “Perlu Review” di bawah.
                    </p>
                </div>
            </div>
        </section>
    @else
        <section class="rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/30 p-4 transition-colors">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-black text-emerald-900 dark:text-emerald-200">Data bulan ini lengkap</h3>
                    <p class="mt-0.5 text-[11px] text-emerald-800 dark:text-emerald-300">
                        Tidak ditemukan missing checkout, sesi lembur aktif, atau jadwal kritis yang belum lengkap.
                    </p>
                </div>
            </div>
        </section>
    @endif

    <!-- Summary KPI Metric Cards (6 cards) -->
    <section class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['Hari Kerja', $summary['effective_work_days'], 'hari', 'text-slate-950 dark:text-slate-100'],
            ['Hadir', $summary['present_days'], 'hari', 'text-emerald-700 dark:text-emerald-400'],
            ['Terlambat', $summary['late_days'], $duration($summary['total_late_minutes']), 'text-amber-700 dark:text-amber-400'],
            ['Tidak Hadir', $summary['absent_days'], 'hari', 'text-rose-700 dark:text-rose-400'],
            ['Libur / Off', $summary['holiday_days'] + $summary['off_days'], 'hari', 'text-slate-700 dark:text-slate-300'],
            ['Attendance Rate', number_format($summary['attendance_rate'], 1).'%', 'Hadir ÷ hari kerja efektif', 'text-slate-950 dark:text-slate-100'],
        ] as [$label, $value, $caption, $tone])
            <div class="min-w-0 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 shadow-xs transition-colors">
                <p class="truncate text-[9px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <p class="mt-2 font-mono text-xl font-black {{ $tone }}">{{ $value }}</p>
                <p class="mt-0.5 truncate text-[9px] font-semibold text-slate-400 dark:text-slate-500">{{ $caption }}</p>
            </div>
        @endforeach
    </section>

    <!-- Time & Overtime Breakdown Cards -->
    <section class="grid gap-3 lg:grid-cols-3">
        <!-- Waktu Reguler -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs transition-colors">
            <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Waktu reguler</h3>
            <dl class="mt-3 space-y-2 text-xs">
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-500 dark:text-slate-400 font-medium">Jam kerja</dt>
                    <dd class="font-mono font-black text-slate-900 dark:text-slate-100">{{ $duration($summary['regular_worked_minutes']) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-500 dark:text-slate-400 font-medium">Terlambat</dt>
                    <dd class="font-mono font-black text-amber-700 dark:text-amber-400">{{ $duration($summary['total_late_minutes']) }}</dd>
                </div>
                @if($summary['total_early_leave_minutes'] > 0)
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-medium">Pulang cepat</dt>
                        <dd class="font-mono font-black text-amber-700 dark:text-amber-400">{{ $duration($summary['total_early_leave_minutes']) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <!-- Izin dan Cuti -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs transition-colors">
            <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Izin dan cuti</h3>
            <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Izin</dt>
                    <dd class="mt-1 font-mono text-lg font-black text-indigo-700 dark:text-indigo-400">{{ $summary['permission_days'] }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Sakit</dt>
                    <dd class="mt-1 font-mono text-lg font-black text-indigo-700 dark:text-indigo-400">{{ $summary['sick_days'] }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Cuti</dt>
                    <dd class="mt-1 font-mono text-lg font-black text-indigo-700 dark:text-indigo-400">{{ $summary['leave_days'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Lembur -->
        <div class="rounded-2xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-950/30 p-4 shadow-xs transition-colors">
            <h3 class="text-[10px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Lembur</h3>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div>
                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Requested</dt>
                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($summary['overtime_requested_minutes']) }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Approved</dt>
                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($summary['overtime_approved_minutes']) }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Actual</dt>
                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($summary['overtime_actual_minutes']) }}</dd>
                </div>
                <div>
                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Credited</dt>
                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($summary['overtime_credited_minutes']) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <!-- Daily Log Section -->
    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs transition-colors">
        <div class="border-b border-slate-100 dark:border-slate-800 px-4 py-4 md:px-5">
            <h3 class="text-sm font-black text-slate-950 dark:text-slate-100">Rincian harian</h3>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Waktu lembur mengikuti tanggal kerja asal, termasuk sesi lintas tengah malam.</p>
        </div>

        @if(empty($recap['daily']))
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-black text-slate-800 dark:text-slate-200">Belum ada rincian</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tidak ada tanggal dalam periode ini.</p>
            </div>
        @else
            <!-- Desktop Table (hidden md:block) -->
            <div class="hidden overflow-x-auto md:block ui-table-container">
                <table class="w-full min-w-[1680px] text-left text-xs border-collapse ui-table">
                    <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 text-[9px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-3 py-3">Jadwal / Shift</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Masuk</th>
                            <th class="px-3 py-3">Pulang</th>
                            <th class="px-3 py-3">Late</th>
                            <th class="px-3 py-3">Pulang Cepat</th>
                            <th class="px-3 py-3">Reguler</th>
                            <th class="px-3 py-3">Izin</th>
                            <th class="px-3 py-3">OT Requested / Approved</th>
                            <th class="px-3 py-3">OT Mulai / Selesai</th>
                            <th class="px-3 py-3">OT Actual / Credited</th>
                            <th class="px-3 py-3">Status Sesi</th>
                            <th class="px-3 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($recap['daily'] as $day)
                        <tr class="align-top {{ $day['needs_review'] ? 'bg-amber-50/40 dark:bg-amber-950/20' : 'hover:bg-slate-50/70 dark:hover:bg-slate-800/40' }} transition-colors">
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $day['date']->translatedFormat('j F Y') }}
                                <span class="block text-[9px] font-medium text-slate-500 dark:text-slate-400">{{ $day['day_name'] }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $day['effective_schedule_label'] }}</span>
                                <span class="block text-[9px] text-slate-500 dark:text-slate-400">{{ $day['shift']?->name ?? ($day['holiday_name'] ?: 'Tanpa shift') }}{{ $day['has_override'] ? ' · Override' : '' }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex rounded-lg border px-2 py-1 text-[9px] font-black {{ $day['status_badge_class'] }}">
                                    {{ $day['status_label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 font-mono font-bold dark:text-slate-200">{{ $time($day['check_in_at']) }}</td>
                            <td class="px-3 py-3 font-mono font-bold dark:text-slate-200">{{ $time($day['check_out_at']) }}</td>
                            <td class="px-3 py-3 font-mono font-bold text-amber-700 dark:text-amber-400">{{ $day['late_minutes'] }}m</td>
                            <td class="px-3 py-3 font-mono font-bold text-amber-700 dark:text-amber-400">{{ $day['early_leave_minutes'] }}m</td>
                            <td class="px-3 py-3 font-mono font-bold dark:text-slate-200">{{ $duration($day['regular_worked_minutes']) }}</td>
                            <td class="px-3 py-3 font-bold text-indigo-700 dark:text-indigo-400">{{ $day['leave_label'] ?: '—' }}</td>
                            <td class="px-3 py-3 font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ $duration($day['overtime_requested_minutes']) }} / {{ $duration($day['overtime_approved_minutes']) }}</td>
                            <td class="px-3 py-3 font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ $time($day['overtime_start_at']) }} / {{ $time($day['overtime_finish_at']) }}</td>
                            <td class="px-3 py-3 font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ $duration($day['overtime_actual_minutes']) }} / {{ $duration($day['overtime_credited_minutes']) }}</td>
                            <td class="px-3 py-3 font-bold text-indigo-700 dark:text-indigo-400 whitespace-nowrap">{{ $day['overtime_session_status'] ? strtoupper(str_replace('_', ' ', $day['overtime_session_status'])) : '—' }}</td>
                            <td class="px-3 py-3 text-xs">
                                @if($day['is_corrected'])
                                    <span class="block text-[9px] font-black text-amber-800 dark:text-amber-300">Dikoreksi Admin</span>
                                @endif
                                @foreach($day['review_issues'] as $issue)
                                    <span class="mt-0.5 block text-[9px] font-bold text-rose-700 dark:text-rose-400">{{ $issue['label'] }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Daily Cards (md:hidden) -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach($recap['daily'] as $day)
                    <article class="p-4 space-y-3 {{ $day['needs_review'] ? 'bg-amber-50/50 dark:bg-amber-950/20' : '' }}">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $day['date']->translatedFormat('j F Y') }} · {{ $day['day_name'] }}</p>
                                <p class="mt-0.5 truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $day['effective_schedule_label'] }} · {{ $day['shift']?->name ?? ($day['holiday_name'] ?: 'Tanpa shift') }}</p>
                            </div>
                            <span class="shrink-0 rounded-lg border px-2 py-1 text-[9px] font-black {{ $day['status_badge_class'] }}">
                                {{ $day['status_label'] }}
                            </span>
                        </div>

                        <!-- Check-in / Out / Regular Worked Grid -->
                        <dl class="grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-200 dark:bg-slate-800 text-center">
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Masuk</dt>
                                <dd class="mt-1 font-mono text-xs font-black dark:text-slate-100">{{ $time($day['check_in_at']) }}</dd>
                            </div>
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Pulang</dt>
                                <dd class="mt-1 font-mono text-xs font-black dark:text-slate-100">{{ $time($day['check_out_at']) }}</dd>
                            </div>
                            <div class="bg-white dark:bg-slate-900 p-2">
                                <dt class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">Reguler</dt>
                                <dd class="mt-1 font-mono text-xs font-black dark:text-slate-100">{{ $duration($day['regular_worked_minutes']) }}</dd>
                            </div>
                        </dl>

                        <!-- Late & Early Leave Row -->
                        <div class="grid grid-cols-2 gap-2 text-[10px]">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2.5">
                                <span class="block text-slate-500 dark:text-slate-400 font-medium">Terlambat</span>
                                <b class="mt-0.5 block font-mono font-bold text-amber-700 dark:text-amber-400">{{ $duration($day['late_minutes']) }}</b>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2.5">
                                <span class="block text-slate-500 dark:text-slate-400 font-medium">Pulang cepat</span>
                                <b class="mt-0.5 block font-mono font-bold text-amber-700 dark:text-amber-400">{{ $duration($day['early_leave_minutes']) }}</b>
                            </div>
                        </div>

                        <!-- Overtime Detail Block -->
                        <div class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/70 dark:bg-indigo-950/40 p-3 text-[10px] space-y-2">
                            <div class="flex items-center justify-between gap-2 border-b border-indigo-200/60 dark:border-indigo-800/60 pb-1.5">
                                <span class="font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Lembur</span>
                                <span class="font-black text-indigo-950 dark:text-indigo-200">{{ $day['overtime_session_status'] ? strtoupper(str_replace('_', ' ', $day['overtime_session_status'])) : 'TANPA SESI' }}</span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2">
                                <div>
                                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Requested / Approved</dt>
                                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($day['overtime_requested_minutes']) }} / {{ $duration($day['overtime_approved_minutes']) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Mulai / Selesai</dt>
                                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $time($day['overtime_start_at']) }} / {{ $time($day['overtime_finish_at']) }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-indigo-600 dark:text-indigo-400 font-medium">Actual / Credited</dt>
                                    <dd class="mt-0.5 font-mono font-black text-indigo-950 dark:text-indigo-200">{{ $duration($day['overtime_actual_minutes']) }} / {{ $duration($day['overtime_credited_minutes']) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Chips / Badges for issues, override, corrections -->
                        @if($day['leave_label'] || $day['has_override'] || $day['is_corrected'] || $day['needs_review'])
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @if($day['leave_label'])
                                    <span class="rounded-md bg-indigo-100 dark:bg-indigo-950/80 px-2 py-0.5 text-[9px] font-black text-indigo-800 dark:text-indigo-300">{{ $day['leave_label'] }}</span>
                                @endif
                                @if($day['has_override'])
                                    <span class="rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[9px] font-black text-slate-700 dark:text-slate-300">Jadwal khusus</span>
                                @endif
                                @if($day['is_corrected'])
                                    <span class="rounded-md bg-amber-100 dark:bg-amber-950/80 px-2 py-0.5 text-[9px] font-black text-amber-900 dark:text-amber-200">Dikoreksi Admin</span>
                                @endif
                                @foreach($day['review_issues'] as $issue)
                                    <span class="rounded-md bg-rose-100 dark:bg-rose-950/80 px-2 py-0.5 text-[9px] font-black text-rose-800 dark:text-rose-300">{{ $issue['label'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
