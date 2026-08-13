@extends('layouts.employee')

@section('title', 'Jadwal Kerja Saya')

@section('content')
<div class="space-y-4">

    <!-- Header & Week Navigation -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex items-center justify-between">
        <a href="{{ route('employee.schedules.index', ['start_date' => $prevWeekDate]) }}" class="px-3 py-2 text-xs font-extrabold text-slate-700 hover:text-slate-900 border border-slate-200 rounded-xl bg-slate-50 transition-colors flex items-center gap-1">
            &larr; Minggu Lalu
        </a>

        <div class="text-center">
            <h3 class="text-xs font-black text-slate-900">
                {{ $startDate->locale('id')->isoFormat('D MMM') }} — {{ $endDate->locale('id')->isoFormat('D MMM YYYY') }}
            </h3>
            <span class="text-[10px] text-rose-600 font-extrabold uppercase tracking-wider block mt-0.5">Jadwal Mingguan</span>
        </div>

        <a href="{{ route('employee.schedules.index', ['start_date' => $nextWeekDate]) }}" class="px-3 py-2 text-xs font-extrabold text-slate-700 hover:text-slate-900 border border-slate-200 rounded-xl bg-slate-50 transition-colors flex items-center gap-1">
            Minggu Depan &rarr;
        </a>
    </div>

    <!-- Error Alert if account unlinked -->
    @if(isset($errorMsg))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-bold">
            {{ $errorMsg }}
        </div>
    @endif

    <!-- Daily Schedule Card List -->
    <div class="space-y-3">
        @if($schedules->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-2 shadow-xs">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h4 class="text-xs font-black text-slate-800">Belum Ada Jadwal</h4>
                <p class="text-[11px] text-slate-500">Jadwal kerja Anda untuk minggu ini belum diterbitkan oleh Admin.</p>
            </div>
        @else
            @foreach($schedules as $sch)
                @php
                    $isToday = $sch->work_date->isToday();
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex items-center justify-between transition-all {{ $isToday ? 'ring-2 ring-rose-500 bg-rose-50/20' : '' }}">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-slate-900">
                                {{ $sch->work_date->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                            </span>
                            @if($isToday)
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-rose-600 text-white rounded-md">Hari Ini</span>
                            @endif
                        </div>
                        
                        @if($sch->notes)
                            <div class="text-[11px] text-slate-500 italic">"{{ $sch->notes }}"</div>
                        @endif
                    </div>

                    <div>
                        @if($sch->schedule_type === 'work' && $sch->shift)
                            <div class="text-right space-y-0.5">
                                <span class="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 font-mono font-extrabold text-xs rounded-lg inline-block">
                                    {{ $sch->shift->code }} ({{ $sch->shift->name }})
                                </span>
                                <div class="text-[11px] font-bold text-slate-700 font-mono">
                                    {{ substr($sch->shift->start_time, 0, 5) }} — {{ substr($sch->shift->end_time, 0, 5) }} WIB
                                </div>
                                @if($sch->shift->crosses_midnight)
                                    <span class="text-[9px] font-bold text-indigo-700 block">🌙 Lintas Tengah Malam</span>
                                @endif
                            </div>
                        @elseif($sch->schedule_type === 'off')
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 border border-slate-200 font-extrabold text-xs rounded-xl inline-block">
                                OFF Pekanan
                            </span>
                        @elseif($sch->schedule_type === 'holiday')
                            <span class="px-3 py-1 bg-amber-50 text-amber-900 border border-amber-200 font-extrabold text-xs rounded-xl inline-block">
                                Libur Toko
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>
@endsection
