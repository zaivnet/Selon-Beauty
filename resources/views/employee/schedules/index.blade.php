@extends('layouts.employee')

@section('title', 'Jadwal Kerja Saya')

@section('content')
<div class="space-y-4">
    <nav class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-xs" aria-label="Jadwal dan rekap">
        <a href="{{ route('employee.schedules.index') }}" aria-current="page" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-slate-900 px-3 text-[11px] font-extrabold text-white">Jadwal</a>
        <a href="{{ route('employee.monthly-recap.show') }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg px-3 text-[11px] font-extrabold text-slate-500 transition hover:bg-slate-50">Rekap Saya</a>
    </nav>
    @if(auth()->user()->role === 'superadmin' || !$employee->attendance_enabled)
        <section class="w-full min-w-0 rounded-2xl border border-amber-200 bg-white shadow-xs" aria-labelledby="schedule-participation-disabled-heading">
            <div class="border-b border-amber-200 bg-amber-50/80 px-5 py-3.5">
                <span class="inline-flex rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-900">Tidak Ikut Absensi</span>
            </div>
            <div class="p-5">
                <h2 id="schedule-participation-disabled-heading" class="text-sm font-extrabold leading-snug text-slate-900">Akun ini tidak diwajibkan mengikuti jadwal kerja.</h2>
                <p class="mt-2 text-[11px] leading-relaxed text-slate-600">Akun login dan akses aplikasi tetap aktif. Jadwal yang pernah tersimpan tidak dihapus, tetapi tidak menjadi kewajiban workforce selama sistem kehadiran dinonaktifkan.</p>
            </div>
        </section>
    @else
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
        <div class="text-center">
            <h3 class="text-xs font-black leading-snug text-slate-900 sm:text-sm">{{ $startDate->locale('id')->isoFormat('D MMM') }} — {{ $endDate->locale('id')->isoFormat('D MMM YYYY') }}</h3>
            <span class="mt-0.5 block text-[10px] font-extrabold uppercase tracking-wider text-rose-600">Jadwal Efektif Mingguan</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
            <a href="{{ route('employee.schedules.index', ['start_date' => $prevWeekDate]) }}" class="flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-2 text-center text-[11px] font-extrabold text-slate-700 transition hover:bg-slate-100">&larr; Minggu Lalu</a>
            <a href="{{ route('employee.schedules.index', ['start_date' => $nextWeekDate]) }}" class="flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-2 text-center text-[11px] font-extrabold text-slate-700 transition hover:bg-slate-100">Minggu Depan &rarr;</a>
        </div>
    </section>

    @if(isset($errorMsg))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-800" role="alert">{{ $errorMsg }}</div>
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
                $cardTone = $isOverride ? 'border-indigo-200 bg-indigo-50/40' : ($isHoliday ? 'border-amber-200 bg-amber-50/40' : ($isSpecial ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white'));
            @endphp
            <article class="rounded-2xl border p-4 shadow-xs {{ $cardTone }} {{ $isToday ? 'ring-2 ring-rose-500 ring-offset-1' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h4 class="text-xs font-black text-slate-900">{{ $date->locale('id')->isoFormat('dddd, D MMMM') }}</h4>
                            @if($isToday)<span class="rounded-md bg-rose-600 px-2 py-0.5 text-[9px] font-black uppercase text-white">Hari Ini</span>@endif
                        </div>
                        <p class="mt-0.5 text-[10px] text-slate-500">{{ $date->isoFormat('YYYY') }}</p>
                    </div>
                    @if($isOverride && $effective['is_working_day'])
                        <span class="rounded-lg border border-indigo-200 bg-indigo-50 px-2 py-1 text-[9px] font-black text-indigo-800">JADWAL KHUSUS</span>
                    @elseif($isOverride)
                        <span class="rounded-lg border border-violet-200 bg-violet-50 px-2 py-1 text-[9px] font-black text-violet-800">LIBUR KHUSUS</span>
                    @elseif($isHoliday)
                        <span class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[9px] font-black text-amber-900">LIBUR</span>
                    @elseif($isSpecial)
                        <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[9px] font-black text-emerald-800">HARI KERJA KHUSUS</span>
                    @elseif($source === 'regular_schedule')
                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[9px] font-black text-slate-600">REGULER</span>
                    @else
                        <span class="rounded-lg border border-slate-200 bg-slate-100 px-2 py-1 text-[9px] font-black text-slate-500">BELUM DITETAPKAN</span>
                    @endif
                </div>

                <div class="mt-3 border-t border-current/10 pt-3">
                    @if($effective['is_working_day'] && $shift)
                        <div class="flex items-end justify-between gap-3">
                            <div class="min-w-0"><p class="truncate text-sm font-black text-slate-900">{{ $shift->name }}</p><p class="mt-0.5 text-[10px] font-bold text-slate-500">{{ $shift->code }}</p></div>
                            <p class="shrink-0 font-mono text-xs font-black text-slate-800">{{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}</p>
                        </div>
                        @if($shift->crosses_midnight)<p class="mt-2 text-[10px] font-bold text-indigo-700">Lintas tengah malam · tetap mengikuti tanggal mulai kerja</p>@endif
                    @elseif($isHoliday)
                        <p class="text-sm font-black text-amber-900">{{ $effective['holiday_name'] ?: 'Hari Libur' }}</p><p class="mt-1 text-[11px] text-amber-800">Tidak ada kewajiban check-in.</p>
                    @elseif($isOverride)
                        <p class="text-sm font-black text-violet-900">Libur Khusus</p><p class="mt-1 text-[11px] text-violet-800">Tidak ada kewajiban check-in.</p>
                    @elseif($isSpecial)
                        <p class="text-xs font-black text-emerald-900">Shift belum ditetapkan</p><p class="mt-1 text-[11px] leading-4 text-emerald-800">Hubungi admin karena hari kerja khusus memerlukan shift reguler atau override.</p>
                    @elseif($source === 'regular_schedule')
                        <p class="text-xs font-black text-slate-700">OFF Pekanan</p><p class="mt-1 text-[11px] text-slate-500">Tidak ada kewajiban check-in.</p>
                    @else
                        <p class="text-xs font-black text-slate-600">Belum ada jadwal</p><p class="mt-1 text-[11px] text-slate-500">Admin belum menetapkan jadwal pada tanggal ini.</p>
                    @endif
                    @if($effective['reason'] && ! $isHoliday)<p class="mt-2 text-[10px] italic leading-4 text-slate-500">{{ $effective['reason'] }}</p>@endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-xs"><p class="text-xs font-black text-slate-800">Jadwal belum tersedia</p><p class="mt-1 text-[11px] text-slate-500">Coba muat ulang halaman atau hubungi admin.</p></div>
        @endforelse
    </section>
    @endif
</div>
@endsection
