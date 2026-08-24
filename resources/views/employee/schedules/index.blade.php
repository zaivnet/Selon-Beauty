@extends('layouts.employee')

@section('title', 'Jadwal Kerja Saya')

@section('content')
<div class="space-y-4">
    <nav class="flex gap-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xs" aria-label="Jadwal dan rekap">
        <a href="{{ route('employee.schedules.index') }}" aria-current="page" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-slate-900 dark:bg-rose-600 px-3 text-[11px] font-extrabold text-white ui-btn ui-btn-primary">Jadwal</a>
        <a href="{{ route('employee.monthly-recap.show') }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg px-3 text-[11px] font-extrabold text-slate-500 dark:text-slate-400 transition hover:bg-slate-50 dark:hover:bg-slate-800">Rekap Saya</a>
    </nav>
    @if(auth()->user()->role === 'superadmin' || !$employee->attendance_enabled)
        <section class="w-full min-w-0 rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-white dark:bg-slate-900 shadow-xs" aria-labelledby="schedule-participation-disabled-heading">
            <div class="border-b border-amber-200 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/40 px-5 py-3.5">
                <span class="inline-flex rounded-lg border border-amber-200 dark:border-amber-800/60 bg-white dark:bg-amber-950/80 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-900 dark:text-amber-200">Tidak Ikut Absensi</span>
            </div>
            <div class="p-5">
                <h2 id="schedule-participation-disabled-heading" class="text-sm font-extrabold leading-snug text-slate-900 dark:text-slate-100">Akun ini tidak diwajibkan mengikuti jadwal kerja.</h2>
                <p class="mt-2 text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">Akun login dan akses aplikasi tetap aktif. Jadwal yang pernah tersimpan tidak dihapus, tetapi tidak menjadi kewajiban workforce selama sistem kehadiran dinonaktifkan.</p>
            </div>
        </section>
    @else
    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
        <div class="text-center">
            <h3 class="text-xs font-black leading-snug text-slate-900 dark:text-slate-100 sm:text-sm">{{ $startDate->locale('id')->isoFormat('D MMM') }} — {{ $endDate->locale('id')->isoFormat('D MMM YYYY') }}</h3>
            <span class="mt-0.5 block text-[10px] font-extrabold uppercase tracking-wider text-rose-600 dark:text-rose-400">Jadwal Efektif Mingguan</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
            <a href="{{ route('employee.schedules.index', ['start_date' => $prevWeekDate]) }}" class="flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-center text-[11px] font-extrabold text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700">&larr; Minggu Lalu</a>
            <a href="{{ route('employee.schedules.index', ['start_date' => $nextWeekDate]) }}" class="flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-center text-[11px] font-extrabold text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700">Minggu Depan &rarr;</a>
        </div>
    </section>

    @if(isset($errorMsg))
        <div class="rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/50 p-4 text-xs font-bold text-rose-800 dark:text-rose-200" role="alert">{{ $errorMsg }}</div>
    @endif

    <section class="space-y-3" aria-label="Jadwal efektif tujuh hari">
        @forelse($schedules as $effective)
            @php
                $date = \Carbon\Carbon::parse($effective['date'], config('app.timezone'));
                $isToday = $date->isToday();
                $source = $effective['source'];
                $shift = $effective['shift'];
                $isOverride = $source === 'employee_override';
                $isHoliday = in_array($source, ['public_holiday', 'company_holiday'], true);
                $isSpecial = $source === 'special_working_day';
                $isCrossOutlet = $effective['is_working_day'] && $effective['work_outlet_id'] && (int) $effective['work_outlet_id'] !== (int) $employee->outlet_id;
                $cardTone = $isCrossOutlet ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/40 dark:bg-indigo-950/30' : ($isOverride ? 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/40 dark:bg-indigo-950/30' : ($isHoliday ? 'border-amber-200 dark:border-amber-800/60 bg-amber-50/40 dark:bg-amber-950/30' : ($isSpecial ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900')));
            @endphp
            <article class="rounded-2xl border p-4 shadow-xs {{ $cardTone }} {{ $isToday ? 'ring-2 ring-rose-500 ring-offset-1 dark:ring-offset-slate-950' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h4 class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $date->locale('id')->isoFormat('dddd, D MMMM') }}</h4>
                            @if($isToday)<span class="rounded-md bg-rose-600 px-2 py-0.5 text-[9px] font-black uppercase text-white ui-btn ui-btn-primary">Hari Ini</span>@endif
                        </div>
                        <p class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{{ $date->isoFormat('YYYY') }}</p>
                    </div>
                    @if($isCrossOutlet)
                        <span class="rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-1 text-[9px] font-black text-indigo-800 dark:text-indigo-300">PENUGASAN</span>
                    @elseif($isOverride && $effective['is_working_day'])
                        <span class="rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-1 text-[9px] font-black text-indigo-800 dark:text-indigo-300">JADWAL KHUSUS</span>
                    @elseif($isOverride)
                        <span class="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-950/50 px-2 py-1 text-[9px] font-black text-violet-800 dark:text-violet-300">LIBUR KHUSUS</span>
                    @elseif($isHoliday)
                        <span class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 px-2 py-1 text-[9px] font-black text-amber-900 dark:text-amber-200">LIBUR</span>
                    @elseif($isSpecial)
                        <span class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-1 text-[9px] font-black text-emerald-800 dark:text-emerald-300">HARI KERJA KHUSUS</span>
                    @elseif($source === 'regular_schedule')
                        <span class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-[9px] font-black text-slate-600 dark:text-slate-300">REGULER</span>
                    @else
                        <span class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-2 py-1 text-[9px] font-black text-slate-500 dark:text-slate-400">BELUM DITETAPKAN</span>
                    @endif
                </div>

                <div class="mt-3 border-t border-current/10 pt-3">
                    @if($effective['is_working_day'] && $shift)
                        <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                            <span>Outlet Kerja: <strong class="{{ $isCrossOutlet ? 'text-indigo-700 dark:text-indigo-300' : '' }}">{{ $effective['work_outlet']?->name ?? $employee->outlet?->name ?? '-' }}</strong></span>
                            @if($isCrossOutlet)
                                <p class="mt-0.5 text-[10px] italic text-slate-400 dark:text-slate-500">Home Outlet Anda tetap {{ $employee->outlet?->name ?? '-' }}.</p>
                            @endif
                        </div>
                        <div class="flex items-end justify-between gap-3 mt-1.5">
                            <div class="min-w-0"><p class="truncate text-sm font-black text-slate-900 dark:text-slate-100">{{ $shift->name }}</p><p class="mt-0.5 text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ $shift->code }}</p></div>
                            <p class="shrink-0 font-mono text-xs font-black text-slate-800 dark:text-slate-200">{{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}</p>
                        </div>
                        @if($shift->crosses_midnight)<p class="mt-2 text-[10px] font-bold text-indigo-700 dark:text-indigo-300">Lintas tengah malam · tetap mengikuti tanggal mulai kerja</p>@endif
                    @elseif($isHoliday)
                        <p class="text-sm font-black text-amber-900 dark:text-amber-200">{{ $effective['holiday_name'] ?: 'Hari Libur' }}</p><p class="mt-1 text-[11px] text-amber-800 dark:text-amber-300">Tidak ada kewajiban check-in.</p>
                    @elseif($isOverride)
                        <p class="text-sm font-black text-violet-900 dark:text-violet-200">Libur Khusus</p><p class="mt-1 text-[11px] text-violet-800 dark:text-violet-300">Tidak ada kewajiban check-in.</p>
                    @elseif($isSpecial)
                        <p class="text-xs font-black text-emerald-900 dark:text-emerald-200">Shift belum ditetapkan</p><p class="mt-1 text-[11px] leading-4 text-emerald-800 dark:text-emerald-300">Hubungi admin karena hari kerja khusus memerlukan shift reguler atau override.</p>
                    @elseif($source === 'regular_schedule')
                        <p class="text-xs font-black text-slate-700 dark:text-slate-300">OFF Pekanan</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Tidak ada kewajiban check-in.</p>
                    @else
                        <p class="text-xs font-black text-slate-600 dark:text-slate-400">Belum ada jadwal</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Admin belum menetapkan jadwal pada tanggal ini.</p>
                    @endif
                    @if($effective['reason'] && ! $isHoliday)<p class="mt-2 text-[10px] italic leading-4 text-slate-500 dark:text-slate-400">{{ $effective['reason'] }}</p>@endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center shadow-xs"><p class="text-xs font-black text-slate-800 dark:text-slate-200">Jadwal belum tersedia</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Coba muat ulang halaman atau hubungi admin.</p></div>
        @endforelse
    </section>
    @endif
</div>
@endsection
