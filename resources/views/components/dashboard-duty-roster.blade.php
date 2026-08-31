@props(['rosterData'])

@php
    $hasOutlets = $rosterData['has_outlets'] ?? false;
    $targetDate = $rosterData['target_date'] ?? date('Y-m-d');
    $targetDateFormatted = $rosterData['target_date_formatted'] ?? '';
    $todayStr = $rosterData['today_str'] ?? date('Y-m-d');
    $tomorrowStr = $rosterData['tomorrow_str'] ?? date('Y-m-d', strtotime('+1 day'));
    $isToday = $rosterData['is_today'] ?? false;
    $isTomorrow = $rosterData['is_tomorrow'] ?? false;
    $authorizedOutlets = $rosterData['authorized_outlets'] ?? collect();
    $selectedOutletId = $rosterData['selected_outlet_id'] ?? null;
    $isAllOutlets = $rosterData['is_all_outlets'] ?? true;
    $outlets = $rosterData['outlets'] ?? [];
    $totalScheduledDuty = $rosterData['total_scheduled_duty'] ?? 0;
@endphp

<section class="ui-card !p-4 sm:!p-5 space-y-3.5" aria-labelledby="duty-roster-heading">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
        <div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-rose-600 dark:bg-rose-500"></span>
                <span class="text-[10px] font-black uppercase tracking-wider text-rose-700 dark:text-rose-400">Roster Operasional</span>
            </div>
            <h2 id="duty-roster-heading" class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 tracking-tight mt-0.5">
                Jadwal Piket
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Petugas kerja terjadwal &bull; <span class="font-bold text-slate-700 dark:text-slate-300">{{ $targetDateFormatted }}</span>
            </p>
        </div>

        <!-- Date Controls Toolbar -->
        <div class="flex flex-wrap items-center gap-1.5">
            <!-- Hari Ini -->
            <a href="{{ request()->fullUrlWithQuery(['roster_date' => $todayStr]) }}"
               class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-1 {{ $isToday ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700' }}">
                <span>Hari Ini</span>
            </a>

            <!-- Besok -->
            <a href="{{ request()->fullUrlWithQuery(['roster_date' => $tomorrowStr]) }}"
               class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-1 {{ $isTomorrow ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700' }}">
                <span>Besok</span>
            </a>

            <!-- Date Picker Form -->
            <form method="GET" action="{{ url()->current() }}" class="inline-flex items-center m-0">
                @foreach(request()->except(['roster_date', 'page']) as $k => $v)
                    @if(is_scalar($v))
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <input type="date"
                       name="roster_date"
                       value="{{ $targetDate }}"
                       aria-label="Pilih Tanggal Jadwal Piket"
                       onchange="this.form.submit()"
                       class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-rose-500 focus:outline-none cursor-pointer">
            </form>
        </div>
    </div>

    @if(! $hasOutlets)
        <!-- Zero Authorized Outlets Empty State -->
        <div class="py-6 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/40 p-4">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Anda belum memiliki akses outlet.</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Hubungi Administrator untuk mendapatkan akses ke outlet operasional.</p>
        </div>
    @else
        @php
            $summary = $rosterData['summary'] ?? [
                'total_duty_count' => 0,
                'active_outlet_count' => 0,
                'unique_shift_count' => 0,
                'total_assignment_count' => 0,
            ];
        @endphp

        <!-- Operational Summary Chip Bar -->
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-xs font-semibold">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80">
                <strong class="font-black text-slate-900 dark:text-white">{{ $summary['total_duty_count'] }}</strong>
                <span class="text-slate-600 dark:text-slate-400">Bertugas</span>
            </span>

            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80">
                <strong class="font-black text-slate-900 dark:text-white">{{ $summary['active_outlet_count'] }}</strong>
                <span class="text-slate-600 dark:text-slate-400">Outlet</span>
            </span>

            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80">
                <strong class="font-black text-slate-900 dark:text-white">{{ $summary['unique_shift_count'] }}</strong>
                <span class="text-slate-600 dark:text-slate-400">Shift</span>
            </span>

            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg {{ $summary['total_assignment_count'] > 0 ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800/80 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200/80 dark:border-slate-700/80' }} border">
                <strong class="font-black">{{ $summary['total_assignment_count'] }}</strong>
                <span class="{{ $summary['total_assignment_count'] > 0 ? 'text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400' }}">Penugasan</span>
            </span>
        </div>

        <!-- Multi-Outlet Subnav Filter (Visible if user has > 1 authorized outlet) -->
        @if($authorizedOutlets->count() > 1)
            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                <!-- Semua Outlet -->
                <a href="{{ request()->fullUrlWithQuery(['roster_outlet_id' => 'all']) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-extrabold transition-all {{ $isAllOutlets ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Semua Outlet
                </a>

                <!-- Individual Outlets -->
                @foreach($authorizedOutlets as $authOutlet)
                    @php
                        $isSelected = ($selectedOutletId === (int) $authOutlet->id);
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['roster_outlet_id' => $authOutlet->id]) }}"
                       class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all {{ $isSelected ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        {{ $authOutlet->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Roster Content: Grouped by Outlet -->
        @if($totalScheduledDuty === 0)
            <div class="py-6 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/40 p-4">
                @if($selectedOutletId !== null)
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Tidak ada karyawan yang dijadwalkan di outlet ini.</p>
                @else
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Tidak ada jadwal kerja pada tanggal ini.</p>
                @endif
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Seluruh karyawan sedang dalam jadwal libur atau belum ditentukan shift kerja.</p>
            </div>
        @else
            <div class="space-y-3.5">
                @foreach($outlets as $outletData)
                    @php
                        $outlet = $outletData['outlet'];
                        $dutyCount = $outletData['total_duty_count'];
                        $offCount = $outletData['off_count'];
                        $shiftSummary = $outletData['shift_summary'];
                        $shifts = $outletData['shifts'];
                    @endphp

                    <div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/30 overflow-hidden">
                        <!-- Outlet Header Banner -->
                        <div class="px-3.5 py-2.5 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>{{ $outlet->name }}</span>
                                </h3>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $dutyCount }} orang bertugas</span>
                                    @if($shiftSummary)
                                        <span>&bull;</span>
                                        <span>{{ $shiftSummary }}</span>
                                    @endif
                                    @if($offCount > 0)
                                        <span>&bull;</span>
                                        <span class="text-slate-400 dark:text-slate-500">Libur / OFF: {{ $offCount }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Shifts and Employees Listing -->
                        <div class="p-3 space-y-3">
                            @if(empty($shifts))
                                <p class="text-xs text-slate-500 dark:text-slate-400 italic py-1">
                                    Tidak ada karyawan yang dijadwalkan piket di outlet ini pada tanggal ini.
                                </p>
                            @else
                                @foreach($shifts as $shiftGroup)
                                    <div class="space-y-2">
                                        <!-- Compact Shift Subheader -->
                                        <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-700/60 pb-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                <h4 class="text-[11px] font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                                    {{ $shiftGroup['shift_name'] }}
                                                </h4>
                                                <span class="text-[11px] font-mono font-semibold text-slate-500 dark:text-slate-400">
                                                    ({{ $shiftGroup['time_range'] }})
                                                </span>
                                            </div>
                                            <span class="text-[11px] font-extrabold text-slate-600 dark:text-slate-400">
                                                {{ $shiftGroup['employee_count'] }} orang
                                            </span>
                                        </div>

                                        <!-- Dense Employee Grid -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                                            @foreach($shiftGroup['employees'] as $empItem)
                                                @php
                                                    $hasExceptionBadge = ($empItem['status_key'] !== 'scheduled');
                                                    $isAssigned = (bool) $empItem['is_temporary_assignment'];
                                                @endphp
                                                <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-between gap-2 hover:border-rose-300 dark:hover:border-rose-700 transition-colors min-w-0">
                                                    <!-- Left: Avatar + Identity + Secondary info -->
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-[10px] flex items-center justify-center overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0">
                                                            @if($empItem['profile_photo_path'])
                                                                <img src="{{ asset('storage/' . $empItem['profile_photo_path']) }}" alt="{{ $empItem['full_name'] }}" class="w-full h-full object-cover">
                                                            @else
                                                                {{ $empItem['initials'] }}
                                                            @endif
                                                        </div>

                                                        <div class="min-w-0">
                                                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate leading-tight">
                                                                {{ $empItem['full_name'] }}
                                                            </p>
                                                            @if($empItem['check_in_time'])
                                                                <p class="text-[10px] font-mono font-bold text-slate-600 dark:text-slate-400 leading-none mt-0.5">
                                                                    In {{ $empItem['check_in_time'] }}
                                                                </p>
                                                            @elseif($isAssigned && $empItem['home_outlet_name'])
                                                                <p class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold leading-none mt-0.5 truncate" title="Home Outlet: {{ $empItem['home_outlet_name'] }}">
                                                                    dari {{ $empItem['home_outlet_name'] }}
                                                                </p>
                                                            @elseif($empItem['job_title'])
                                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium leading-none mt-0.5 truncate">
                                                                    {{ $empItem['job_title'] }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Right: Exception / Attendance Badges -->
                                                    <div class="shrink-0 flex items-center gap-1">
                                                        @if($isAssigned)
                                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                                                PENUGASAN
                                                            </span>
                                                        @endif

                                                        @if($hasExceptionBadge)
                                                            <span class="ui-badge text-[10px] !py-0.5 !px-1.5 {{ $empItem['badge_class'] }}">
                                                                {{ $empItem['status_label'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</section>
