@extends('layouts.employee')

@section('title', 'Beranda Karyawan')

@section('content')
<div class="space-y-4">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($correctedAttendance)
        <div id="attendance-{{ $correctedAttendance->id }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-extrabold">Absensi {{ $correctedAttendance->work_date->format('d M Y') }} · Dikoreksi Admin</p>
                @if($correctedAttendance->corrected_at)<span class="text-[10px] font-bold">{{ $correctedAttendance->corrected_at->format('d M Y H:i') }}</span>@endif
            </div>
            <p class="mt-2">Masuk <strong>{{ $correctedAttendance->check_in_at?->format('H:i') ?? '—' }}</strong> · Pulang <strong>{{ $correctedAttendance->check_out_at?->format('H:i') ?? '—' }}</strong> · Worked <strong>{{ $correctedAttendance->worked_minutes }} menit</strong></p>
        </div>
    @endif

    <!-- PWA Install Prompt Banner (Shown only for attendance participants when installable) -->
    @if(!$employee || ($user->role !== 'superadmin' && $employee->attendance_enabled !== false))
    <div id="pwa-install-banner" class="hidden bg-gradient-to-r from-rose-700 via-rose-600 to-pink-600 text-white rounded-2xl p-4 shadow-md flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center font-black text-xs shrink-0">
                SB
            </div>
            <div>
                <h4 class="text-xs font-black">Pasang Aplikasi SELON BEAUTY</h4>
                <p class="text-[11px] text-rose-100 font-medium">Tambahkan ke Home Screen untuk presensi lebih cepat.</p>
            </div>
        </div>
        <button type="button" onclick="triggerPwaInstall()" class="px-3.5 py-2 bg-white text-rose-700 hover:bg-rose-50 font-extrabold text-xs rounded-xl shadow-xs shrink-0 cursor-pointer">
            Pasang
        </button>
    </div>
    @endif

    <!-- Greeting & Header Card -->
    <div class="bg-gradient-to-br from-rose-600 to-rose-700 text-white rounded-2xl p-5 shadow-lg shadow-rose-900/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-rose-200 font-medium">Selamat Datang 👋</p>
                <h2 class="text-xl font-extrabold tracking-tight mt-0.5">{{ $employee?->full_name ?? $user->name }}</h2>
                <p class="text-xs text-rose-100 mt-1 font-semibold">{{ $today }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 border-2 border-white/30 text-white flex items-center justify-center font-bold text-lg">
                {{ substr($employee?->full_name ?? $user->name, 0, 2) }}
            </div>
        </div>
    </div>

    @if($user->role === 'superadmin' || ($employee && $employee->attendance_enabled === false))
        <section class="w-full min-w-0 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-xs" aria-labelledby="attendance-disabled-heading">
            <div class="border-b border-amber-200 bg-amber-50/80 px-4 py-3.5 sm:px-5">
                <span class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-900">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    Tidak Ikut Absensi
                </span>
            </div>
            <div class="p-5 sm:p-6">
                <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="attendance-disabled-heading" class="text-base font-extrabold leading-snug text-slate-900">Akun ini tidak diwajibkan mengikuti sistem kehadiran.</h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">Akun login dan akses aplikasi Anda tetap aktif sesuai role. Status ini hanya menonaktifkan kewajiban jadwal, absensi, izin, dan lembur.</p>
                        <div class="mt-4 grid grid-cols-1 gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:grid-cols-3" aria-label="Konsep akun yang saling terpisah">
                            <span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-center">Status Karyawan</span>
                            <span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-center">Role Aplikasi</span>
                            <span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-center text-amber-900">Sistem Kehadiran</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
    <!-- Quick Menu Shortcuts -->
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('employee.leave-requests.index') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs hover:border-rose-300 transition-all flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-extrabold text-slate-900">Izin & Cuti</h4>
                <p class="text-[10px] text-slate-500 font-medium">Pengajuan Cuti</p>
            </div>
        </a>

        <a href="{{ route('employee.overtime-requests.index') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs hover:border-rose-300 transition-all flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center font-bold shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-extrabold text-slate-900">Lembur</h4>
                <p class="text-[10px] text-slate-500 font-medium">Pengajuan Lembur</p>
            </div>
        </a>
    </div>

    <!-- Today Shift Card -->
    @php
        $effectiveShift = $todayEffective && $todayEffective['is_working_day'] ? $todayEffective['shift'] : null;
        $activeShift = $todaySchedule?->shift;
        $displayShift = $effectiveShift ?: $activeShift;
        $isCarryoverShift = ! $effectiveShift && $activeShift;
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jadwal Efektif Hari Ini</span>
            @if($isCarryoverShift)
                <span class="rounded-lg border border-indigo-200 bg-indigo-50 px-2 py-1 text-[9px] font-black text-indigo-800">SHIFT AKTIF · LINTAS HARI</span>
            @elseif($todayEffective)
                @php($effectiveSource = $todayEffective['source'])
                <span class="rounded-lg border px-2 py-1 text-[9px] font-black {{ $effectiveSource === 'employee_override' ? 'border-indigo-200 bg-indigo-50 text-indigo-800' : (in_array($effectiveSource, ['public_holiday', 'company_holiday'], true) ? 'border-amber-200 bg-amber-50 text-amber-900' : ($effectiveSource === 'special_working_day' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600')) }}">{{ $todayEffective['label'] }}</span>
            @endif
        </div>

        @if($displayShift)
                <div class="{{ $isCarryoverShift || $todayEffective['source'] === 'employee_override' ? 'bg-indigo-50/70 border-indigo-200/80' : ($todayEffective['source'] === 'special_working_day' ? 'bg-emerald-50/70 border-emerald-200/80' : 'bg-rose-50/70 border-rose-200/80') }} border rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <div><span class="mb-1 inline-block rounded-md border border-current/20 bg-white/60 px-2 py-0.5 font-mono text-[10px] font-black text-rose-800">{{ $displayShift->code }}</span><h4 class="text-base font-extrabold text-slate-900">{{ $displayShift->name }}</h4></div>
                        @if($displayShift->crosses_midnight)
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">
                                Lintas Tengah Malam
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 font-mono">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ substr($displayShift->start_time, 0, 5) }} — {{ substr($displayShift->end_time, 0, 5) }} WIB</span>
                        <span class="text-[11px] text-slate-500 font-normal">({{ $displayShift->formatted_work_hours }})</span>
                    </div>
                    @if($isCarryoverShift)
                        <p class="text-xs text-indigo-700 border-t border-indigo-200/60 pt-2 mt-1">Shift ini dimulai pada work date sebelumnya dan tetap aktif sampai waktu pulang.</p>
                    @elseif($todayEffective['reason'])
                        <p class="text-xs text-slate-600 italic border-t border-rose-200/60 pt-2 mt-1">
                            "{{ $todayEffective['reason'] }}"
                        </p>
                    @endif
                </div>
        @elseif($todayEffective && in_array($todayEffective['source'], ['public_holiday', 'company_holiday'], true))
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center space-y-1">
                    <p class="text-xs font-black text-amber-900">LIBUR · {{ $todayEffective['holiday_name'] }}</p>
                    <p class="text-[11px] text-amber-800">Tidak ada kewajiban check-in reguler hari ini.</p>
                </div>
        @elseif($todayEffective && $todayEffective['source'] === 'employee_override')
                <div class="bg-violet-50 border border-violet-200 rounded-xl p-4 text-center space-y-1"><p class="text-xs font-black text-violet-900">LIBUR KHUSUS</p><p class="text-[11px] text-violet-800">Jadwal Anda diubah menjadi libur oleh admin.</p></div>
        @elseif($todayEffective && $todayEffective['source'] === 'special_working_day')
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center space-y-1"><p class="text-xs font-black text-emerald-900">Hari Kerja Khusus</p><p class="text-[11px] text-emerald-800">Shift belum ditetapkan. Hubungi admin sebelum melakukan presensi.</p></div>
        @elseif($todayEffective && $todayEffective['source'] === 'regular_schedule')
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center space-y-1"><p class="text-xs font-bold text-slate-800">Jadwal Libur Pekanan (OFF)</p><p class="text-[11px] text-slate-500">Tidak ada kewajiban check-in hari ini.</p></div>
        @else
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-xs font-semibold text-slate-700">Jadwal Kerja Belum Ditetapkan</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Owner/Admin belum mengatur jadwal kerja Anda untuk hari ini.</p>
            </div>
        @endif
    </div>

    <!-- Attendance Summary / Record Card if Already Recorded -->
    @if($todayAttendance)
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Status Presensi Hari Ini</span>
            
            <div class="space-y-3">
                @if($todayAttendance->is_manually_adjusted)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] font-bold text-amber-800">
                        Dikoreksi Admin @if($todayAttendance->corrected_at) · Terakhir {{ $todayAttendance->corrected_at->format('d M Y H:i') }} @endif
                    </div>
                @endif
                <!-- Check-in Status Box -->
                <div class="p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-xl flex items-center gap-3">
                    @if($todayAttendance->check_in_selfie_path)
                        <img src="{{ route('attendance.selfie', ['record' => $todayAttendance->id, 'type' => 'check_in']) }}" alt="Selfie Masuk" class="w-14 h-14 rounded-lg object-cover border border-emerald-300 shadow-2xs shrink-0 bg-slate-200">
                    @else
                        <div class="w-14 h-14 rounded-lg bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-700 font-bold text-xs shrink-0">
                            Masuk
                        </div>
                    @endif
                    
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-emerald-900">✓ Absen Masuk Berhasil</span>
                            <span class="text-xs font-mono font-bold text-emerald-800">{{ $todayAttendance->check_in_at?->format('H:i') }} WIB</span>
                        </div>
                        <p class="text-[11px] text-emerald-700 mt-0.5 font-medium">
                            Status:
                            @if($todayAttendance->status === 'late')
                                <span class="text-rose-700 font-extrabold">Terlambat ({{ $todayAttendance->late_minutes }}m)</span>
                            @else
                                <span class="text-emerald-800 font-extrabold">Tepat Waktu</span>
                            @endif
                        </p>
                        <p class="text-[10px] text-emerald-600 mt-0.5">📍 {{ $todayAttendance->location?->name ?? 'SELON BEAUTY' }} (±{{ round($todayAttendance->check_in_accuracy_meters) }}m)</p>
                    </div>
                </div>

                <!-- Check-out Status Box if completed -->
                @if($todayAttendance->check_out_at)
                    <div class="p-3.5 bg-indigo-50/80 border border-indigo-200 rounded-xl flex items-center gap-3">
                        @if($todayAttendance->check_out_selfie_path)
                            <img src="{{ route('attendance.selfie', ['record' => $todayAttendance->id, 'type' => 'check_out']) }}" alt="Selfie Pulang" class="w-14 h-14 rounded-lg object-cover border border-indigo-300 shadow-2xs shrink-0 bg-slate-200">
                        @else
                            <div class="w-14 h-14 rounded-lg bg-indigo-100 border border-indigo-300 flex items-center justify-center text-indigo-700 font-bold text-xs shrink-0">
                                Pulang
                            </div>
                        @endif

                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-indigo-900">✓ Absen Pulang Berhasil</span>
                                <span class="text-xs font-mono font-bold text-indigo-800">{{ $todayAttendance->check_out_at?->format('H:i') }} WIB</span>
                            </div>
                            <p class="text-[11px] text-indigo-700 mt-0.5 font-medium">
                                Worked Time: <span class="font-bold text-slate-900 font-mono">{{ floor($todayAttendance->worked_minutes / 60) }}j {{ $todayAttendance->worked_minutes % 60 }}m</span>
                            </p>
                            <p class="text-[10px] text-indigo-600 mt-0.5">📍 {{ $todayAttendance->location?->name ?? 'SELON BEAUTY' }} (±{{ round($todayAttendance->check_out_accuracy_meters) }}m)</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @elseif($todayLeave)
        <!-- Approved Leave Banner Card -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 shadow-xs text-center space-y-2">
            <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <span class="text-[10px] font-extrabold text-indigo-700 uppercase tracking-wider block">Status Presensi Hari Ini</span>
                <h3 class="text-xl font-black text-indigo-900 mt-0.5">{{ strtoupper($todayLeave->type_label) }}</h3>
                <p class="text-xs text-indigo-800 font-medium max-w-xs mx-auto mt-1">
                    Anda sedang dalam masa <strong>{{ $todayLeave->type_label }}</strong> yang telah disetujui untuk hari ini ({{ $todayLeave->start_date->format('d M Y') }} - {{ $todayLeave->end_date->format('d M Y') }}).
                </p>
                <p class="text-[11px] text-indigo-600 italic mt-2">Tombol Absen Masuk tidak aktif pada hari izin/cuti ini.</p>
            </div>
        </div>
    @endif

    <!-- Attendance Form Section (GPS & Selfie) -->
    @if($todayOvertime)
        <div class="bg-indigo-50 rounded-2xl p-5 border border-indigo-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-black text-indigo-700 uppercase tracking-widest">Lembur Hari Ini</span>
                    <p class="text-sm font-extrabold text-slate-900 mt-1">{{ !$todayOvertime->session ? 'Disetujui · Belum Dimulai' : ($todayOvertime->session->isActive() ? 'Sedang Lembur' : ($todayOvertime->session->isCancelled() ? 'Dibatalkan Admin' : 'Selesai')) }}</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-extrabold text-indigo-700 border border-indigo-200">
                    {{ \App\Models\OvertimeSession::formatMinutes($todayOvertime->approved_minutes) }} approved
                </span>
            </div>
            @if($todayOvertime->session)
                @if($todayOvertime->session->corrected_at)
                    <p class="rounded-lg bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-800">Dikoreksi Admin · {{ $todayOvertime->session->corrected_at->format('d M Y H:i') }}</p>
                @endif
                <div class="text-xs text-slate-700">
                    Mulai <strong>{{ $todayOvertime->session->check_in_at?->format('H:i') }}</strong>
                    @if($todayOvertime->session->isCancelled())
                        · Credited <strong>0m</strong>
                    @elseif($todayOvertime->session->isCompleted())
                        · Selesai <strong>{{ $todayOvertime->session->check_out_at?->format('H:i') }}</strong><br>
                        Actual <strong>{{ \App\Models\OvertimeSession::formatMinutes($todayOvertime->session->actual_minutes) }}</strong>
                        · Credited <strong>{{ \App\Models\OvertimeSession::formatMinutes($todayOvertime->session->credited_minutes) }}</strong>
                    @else
                        · Durasi berjalan {{ \App\Models\OvertimeSession::formatMinutes($todayOvertime->session->runningMinutes()) }}
                    @endif
                </div>
            @endif
            @php($overtimeCanStartWithoutAttendance = $todayEffective && ! $todayEffective['is_working_day'])
            @if(((($overtimeCanStartWithoutAttendance || $todayAttendance?->check_out_at) && !$todayOvertime->session && $todayOvertime->approved_minutes > 0)) || $todayOvertime->session?->isActive())
                <a href="{{ route('employee.overtime-requests.index', ['highlight' => $todayOvertime->id]) }}#overtime-{{ $todayOvertime->id }}" class="flex min-h-[44px] w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-extrabold text-white">
                    {{ !$todayOvertime->session ? 'Mulai Lembur' : 'Selesai Lembur' }}
                </a>
            @elseif(!$todayOvertime->session && ! $overtimeCanStartWithoutAttendance)
                <p class="text-[11px] font-semibold text-indigo-800">Selesaikan absensi kerja reguler terlebih dahulu.</p>
            @endif
        </div>
    @endif

    @if($todaySchedule && $todaySchedule->schedule_type === 'work' && (!$todayAttendance || !$todayAttendance->check_out_at) && !$todayLeave)
        <!-- GPS Geofence Section -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">1. Lokasi GPS</span>
                <span id="gps-badge-status" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-slate-400 animate-pulse"></span> Initializing...
                </span>
            </div>

            <!-- Location Status Box -->
            <div id="gps-info-box" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-xs font-extrabold text-slate-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>SELON BEAUTY</span>
                        </h5>
                        <p id="gps-status-text" class="text-[11px] text-slate-600 mt-0.5 font-medium">Mendeteksi lokasi perangkat...</p>
                    </div>

                    <button type="button" onclick="detectGPSLocation()" class="px-3 py-1.5 border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 font-bold text-[11px] rounded-lg shadow-2xs transition-colors flex items-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Perbarui Lokasi</span>
                    </button>
                </div>

                <!-- GPS Detail Metrics Grid -->
                <div id="gps-metrics-grid" class="hidden grid grid-cols-2 gap-2 border-t border-slate-200/80 pt-3 text-xs">
                    <div class="bg-white p-2.5 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] text-slate-500 font-medium block">Jarak ke Toko</span>
                        <span id="gps-metric-distance" class="font-extrabold font-mono text-slate-900 text-sm">-- m</span>
                    </div>
                    <div class="bg-white p-2.5 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] text-slate-500 font-medium block">Akurasi GPS</span>
                        <span id="gps-metric-accuracy" class="font-extrabold font-mono text-slate-900 text-sm">±-- m</span>
                    </div>
                </div>
            </div>
        </div>

        @if($requireSelfie)
        <!-- Selfie Camera Section -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">2. Foto Selfie Bukti</span>
                <span id="camera-badge-status" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span> Kamera Belum Dibuka
                </span>
            </div>

            <!-- Camera Viewport / Preview Box -->
            <div class="relative w-full overflow-hidden rounded-xl bg-slate-900 border border-slate-200">
                <!-- Unopened Camera Placeholder -->
                <div id="camera-placeholder" class="w-full h-52 sm:h-60 rounded-xl bg-slate-100 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center p-4 text-center">
                    <div class="w-12 h-12 rounded-full bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <p class="text-xs font-extrabold text-slate-800">Kamera Belum Dibuka</p>
                    <p class="text-[11px] text-slate-500 mt-1 max-w-xs">Klik tombol "Buka Kamera" di bawah untuk mengambil foto selfie presensi.</p>
                </div>

                <!-- Video Element -->
                <video id="camera-video" class="w-full h-52 sm:h-60 object-cover hidden" autoplay playsinline muted></video>

                <!-- Hidden Canvas for capture -->
                <canvas id="camera-canvas" class="hidden"></canvas>

                <!-- Image Preview after capture -->
                <img id="selfie-preview" class="w-full h-52 sm:h-60 object-cover hidden" alt="Preview Selfie">
            </div>

            <!-- Camera Status Text -->
            <p id="camera-status-text" class="text-xs text-slate-600 font-medium text-center">
                Posisikan wajah Anda dengan jelas di depan kamera.
            </p>

            <!-- Camera Action Buttons -->
            <div class="space-y-2">
                <!-- Initial Action: Open Camera -->
                <button type="button" id="btn-open-camera" onclick="openCamera()" class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-xl text-xs flex items-center justify-center gap-2 shadow-sm transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Buka Kamera</span>
                </button>

                <!-- Active Camera Actions: Capture Photo & Close -->
                <div id="camera-actions-active" class="hidden grid grid-cols-2 gap-2">
                    <button type="button" id="btn-close-camera" onclick="closeCamera()" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl text-xs transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="button" id="btn-capture-photo" onclick="capturePhoto()" class="py-2.5 px-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Ambil Foto</span>
                    </button>
                </div>

                <!-- Captured Actions: Retake & Confirm -->
                <div id="camera-actions-captured" class="hidden grid grid-cols-2 gap-2">
                    <button type="button" onclick="retakePhoto()" class="py-2.5 px-3 border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 font-extrabold rounded-xl text-xs transition-colors flex items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Foto Ulang</span>
                    </button>
                    <button type="button" id="btn-use-photo" onclick="confirmPhoto()" class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs transition-colors flex items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Gunakan Foto</span>
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Submit Attendance Form Container -->
        <div id="absen-card" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3">
            @if(!$todayAttendance)
                <!-- Check-In Form -->
                <form id="form-check-in" action="{{ route('employee.attendance.check-in') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude" id="checkin_latitude">
                    <input type="hidden" name="longitude" id="checkin_longitude">
                    <input type="hidden" name="accuracy" id="checkin_accuracy">
                    <input type="hidden" name="selfie_base64" id="selfie_base64_checkin">
                    <input type="file" name="selfie" id="selfie_file_checkin" class="hidden" accept="image/*">

                    <button type="submit" id="btn-check-in" disabled class="w-full py-3.5 px-4 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold rounded-xl text-sm flex items-center justify-center gap-2 shadow-md transition-all opacity-50 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Absen Masuk (Lengkapi Lokasi{{ $requireSelfie ? ' & Selfie' : '' }})</span>
                    </button>
                </form>
            @elseif(!$todayAttendance->check_out_at)
                <!-- Check-Out Form -->
                <form id="form-check-out" action="{{ route('employee.attendance.check-out') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude" id="checkout_latitude">
                    <input type="hidden" name="longitude" id="checkout_longitude">
                    <input type="hidden" name="accuracy" id="checkout_accuracy">
                    <input type="hidden" name="selfie_base64" id="selfie_base64_checkout">
                    <input type="file" name="selfie" id="selfie_file_checkout" class="hidden" accept="image/*">

                    <button type="submit" id="btn-check-out" disabled class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-extrabold rounded-xl text-sm flex items-center justify-center gap-2 shadow-md transition-all opacity-50 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Absen Keluar (Lengkapi Lokasi{{ $requireSelfie ? ' & Selfie' : '' }})</span>
                    </button>
                </form>
            @endif
        </div>
    @elseif($todayEffective && ! $todayEffective['is_working_day'])
        <div class="bg-white rounded-2xl p-5 border border-slate-200 text-center">
            <button disabled class="w-full py-3.5 px-4 bg-slate-200 text-slate-500 font-bold rounded-xl text-sm flex items-center justify-center gap-2 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Presensi Nonaktif (Bukan Shift Kerja)</span>
            </button>
        </div>
    @endif

    @endif

</div>

<!-- Client-side Browser Geolocation & Camera Script -->
@if(!$employee || ($user->role !== 'superadmin' && $employee->attendance_enabled !== false))
<script>
let isGpsValid = false;
const isSelfieRequired = @json($requireSelfie);
let isSelfieConfirmed = !isSelfieRequired;
let cameraStream = null;

// Haversine Distance Calculation for Instant UX Feedback
function calculateHaversineMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
}

// ----------------------------------------------------
// CAMERA LOGIC & STATE MACHINE
// ----------------------------------------------------

function updateCameraUI(state, message = '') {
    const badge = document.getElementById('camera-badge-status');
    const statusText = document.getElementById('camera-status-text');
    const placeholder = document.getElementById('camera-placeholder');
    const video = document.getElementById('camera-video');
    const preview = document.getElementById('selfie-preview');

    const btnOpen = document.getElementById('btn-open-camera');
    const actionsActive = document.getElementById('camera-actions-active');
    const actionsCaptured = document.getElementById('camera-actions-captured');

    if (statusText && message) statusText.innerText = message;

    if (state === 'unopened') {
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-400"></span> Kamera Belum Dibuka';
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (video) video.classList.add('hidden');
        if (preview) preview.classList.add('hidden');
        if (btnOpen) btnOpen.classList.remove('hidden');
        if (actionsActive) actionsActive.classList.add('hidden');
        if (actionsCaptured) actionsCaptured.classList.add('hidden');
    } else if (state === 'requesting') {
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Meminta Izin Kamera...';
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (video) video.classList.add('hidden');
        if (preview) preview.classList.add('hidden');
        if (btnOpen) btnOpen.classList.add('hidden');
        if (actionsActive) actionsActive.classList.add('hidden');
        if (actionsCaptured) actionsCaptured.classList.add('hidden');
    } else if (state === 'active') {
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Kamera Aktif';
        }
        if (placeholder) placeholder.classList.add('hidden');
        if (video) video.classList.remove('hidden');
        if (preview) preview.classList.add('hidden');
        if (btnOpen) btnOpen.classList.add('hidden');
        if (actionsActive) actionsActive.classList.remove('hidden');
        if (actionsCaptured) actionsCaptured.classList.add('hidden');
    } else if (state === 'captured') {
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-indigo-500"></span> Foto Diambil';
        }
        if (placeholder) placeholder.classList.add('hidden');
        if (video) video.classList.add('hidden');
        if (preview) preview.classList.remove('hidden');
        if (btnOpen) btnOpen.classList.add('hidden');
        if (actionsActive) actionsActive.classList.add('hidden');
        if (actionsCaptured) actionsCaptured.classList.remove('hidden');
    } else if (state === 'confirmed') {
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span> Selfie Dikonfirmasi';
        }
        if (placeholder) placeholder.classList.add('hidden');
        if (video) video.classList.add('hidden');
        if (preview) preview.classList.remove('hidden');
        if (btnOpen) btnOpen.classList.add('hidden');
        if (actionsActive) actionsActive.classList.add('hidden');
        if (actionsCaptured) actionsCaptured.classList.remove('hidden');
    } else {
        // Error state
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-500"></span> Akses Kamera Gagal';
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (video) video.classList.add('hidden');
        if (preview) preview.classList.add('hidden');
        if (btnOpen) btnOpen.classList.remove('hidden');
        if (actionsActive) actionsActive.classList.add('hidden');
        if (actionsCaptured) actionsCaptured.classList.add('hidden');
    }

    evaluateSubmitButtons();
}

function stopCameraStream() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

async function openCamera() {
    updateCameraUI('requesting', 'Meminta izin akses kamera browser...');

    try {
        const constraints = {
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (err) {
            // Fallback if facingMode user fails or is overconstrained
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
        }

        const video = document.getElementById('camera-video');
        if (video) {
            video.srcObject = cameraStream;
            await video.play();
        }
        updateCameraUI('active', 'Kamera aktif. Posisikan wajah Anda pada frame lalu klik "Ambil Foto".');
    } catch (err) {
        stopCameraStream();
        let msg = 'Gagal mengakses kamera.';
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            msg = 'Akses kamera ditolak. Izinkan akses kamera di pengaturan browser/perangkat Anda.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            msg = 'Perangkat kamera tidak ditemukan atau tidak tersedia.';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            msg = 'Kamera sedang digunakan oleh aplikasi/tab lain.';
        }
        updateCameraUI('error', msg);
    }
}

function closeCamera() {
    stopCameraStream();
    isSelfieConfirmed = false;
    updateCameraUI('unopened', 'Kamera ditutup. Klik "Buka Kamera" jika ingin mengambil foto.');
}

function capturePhoto() {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('camera-canvas');
    const preview = document.getElementById('selfie-preview');

    if (!video || !canvas || !preview) return;

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    preview.src = dataUrl;

    stopCameraStream();
    isSelfieConfirmed = false;
    updateCameraUI('captured', 'Foto selfie berhasil diambil. Klik "Gunakan Foto" untuk mengonfirmasi.');
}

function retakePhoto() {
    isSelfieConfirmed = false;

    const checkinBase64 = document.getElementById('selfie_base64_checkin');
    const checkoutBase64 = document.getElementById('selfie_base64_checkout');
    if (checkinBase64) checkinBase64.value = '';
    if (checkoutBase64) checkoutBase64.value = '';

    const checkinFile = document.getElementById('selfie_file_checkin');
    const checkoutFile = document.getElementById('selfie_file_checkout');
    if (checkinFile) checkinFile.value = '';
    if (checkoutFile) checkoutFile.value = '';

    openCamera();
}

function confirmPhoto() {
    const preview = document.getElementById('selfie-preview');
    if (!preview || !preview.src || preview.src === '') return;

    const dataUrl = preview.src;

    const checkinBase64 = document.getElementById('selfie_base64_checkin');
    const checkoutBase64 = document.getElementById('selfie_base64_checkout');
    if (checkinBase64) checkinBase64.value = dataUrl;
    if (checkoutBase64) checkoutBase64.value = dataUrl;

    // Convert DataURL to File & assign to input via DataTransfer
    try {
        const arr = dataUrl.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        const blob = new Blob([u8arr], { type: mime });
        const file = new File([blob], "selfie.jpg", { type: mime });
        const dt = new DataTransfer();
        dt.items.add(file);

        const checkinFile = document.getElementById('selfie_file_checkin');
        const checkoutFile = document.getElementById('selfie_file_checkout');
        if (checkinFile) checkinFile.files = dt.files;
        if (checkoutFile) checkoutFile.files = dt.files;
    } catch (e) {
        // Fallback gracefully to base64
    }

    isSelfieConfirmed = true;
    updateCameraUI('confirmed', '✓ Foto selfie berhasil dikonfirmasi dan siap dikirim.');
}

// ----------------------------------------------------
// GPS LOGIC & GEOFENCE EVALUATION
// ----------------------------------------------------

function updateGpsUIState(state, message, distanceMeters = null, accuracyMeters = null) {
    const badge = document.getElementById('gps-badge-status');
    const statusText = document.getElementById('gps-status-text');
    const metricsGrid = document.getElementById('gps-metrics-grid');
    const distEl = document.getElementById('gps-metric-distance');
    const accEl = document.getElementById('gps-metric-accuracy');

    if (statusText) statusText.innerText = message;

    if (state === 'requesting') {
        isGpsValid = false;
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-400 animate-pulse"></span> Mendeteksi...';
        }
        if (metricsGrid) metricsGrid.classList.add('hidden');
    } else if (state === 'ready') {
        isGpsValid = true;
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span> Dalam Area';
        }
        if (metricsGrid) metricsGrid.classList.remove('hidden');
        if (distEl) distEl.innerText = (distanceMeters !== null ? distanceMeters : '--') + ' m';
        if (accEl) accEl.innerText = '±' + (accuracyMeters !== null ? Math.round(accuracyMeters) : '--') + ' m';
    } else {
        isGpsValid = false;
        if (badge) {
            badge.className = "inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-full";
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-500"></span> Tidak Valid';
        }
        if (metricsGrid) metricsGrid.classList.remove('hidden');
        if (distEl) distEl.innerText = (distanceMeters !== null ? distanceMeters : '--') + ' m';
        if (accEl) accEl.innerText = '±' + (accuracyMeters !== null ? Math.round(accuracyMeters) : '--') + ' m';
    }

    evaluateSubmitButtons();
}

function detectGPSLocation() {
    if (!navigator.geolocation) {
        updateGpsUIState('error', 'Browser Anda tidak mendukung fitur lokasi GPS.');
        return;
    }

    updateGpsUIState('requesting', 'Mendeteksi koordinat dan akurasi lokasi GPS...');

    const options = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const acc = position.coords.accuracy;

            // Update hidden form inputs
            const checkinLat = document.getElementById('checkin_latitude');
            const checkinLng = document.getElementById('checkin_longitude');
            const checkinAcc = document.getElementById('checkin_accuracy');
            if (checkinLat) checkinLat.value = lat;
            if (checkinLng) checkinLng.value = lng;
            if (checkinAcc) checkinAcc.value = acc;

            const checkoutLat = document.getElementById('checkout_latitude');
            const checkoutLng = document.getElementById('checkout_longitude');
            const checkoutAcc = document.getElementById('checkout_accuracy');
            if (checkoutLat) checkoutLat.value = lat;
            if (checkoutLng) checkoutLng.value = lng;
            if (checkoutAcc) checkoutAcc.value = acc;

            // Fetch active location metadata from DOM/view context
            @if(isset($activeLocation) && $activeLocation)
                const storeLat = {{ $activeLocation->latitude }};
                const storeLng = {{ $activeLocation->longitude }};
                const maxRadius = {{ $activeLocation->radius_meters }};
                const maxAccuracy = {{ $activeLocation->max_accuracy_meters }};

                const distance = calculateHaversineMeters(lat, lng, storeLat, storeLng);

                if (acc > maxAccuracy) {
                    updateGpsUIState('low_accuracy', `Lokasi Belum Cukup Akurat (Akurasi GPS ±${Math.round(acc)}m). Batas maksimum ±${maxAccuracy}m. Coba perbarui lokasi di tempat terbuka.`, null, acc);
                } else if (distance > maxRadius) {
                    updateGpsUIState('outside_radius', `Anda berada di luar area absensi SELON BEAUTY (Jarak: ${distance}m, Maks: ${maxRadius}m).`, distance, acc);
                } else {
                    updateGpsUIState('ready', `✓ Lokasi terverifikasi dalam area absensi SELON BEAUTY.`, distance, acc);
                }
            @else
                updateGpsUIState('ready', '✓ Koordinat lokasi berhasil terdeteksi.', 0, acc);
            @endif
        },
        function(error) {
            let errorMsg = 'Lokasi gagal dideteksi.';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = 'Akses lokasi ditolak. Izinkan akses lokasi pada browser agar dapat melakukan absensi.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = 'Lokasi tidak dapat ditemukan. Pastikan GPS/lokasi perangkat aktif.';
                    break;
                case error.TIMEOUT:
                    errorMsg = 'Pencarian lokasi terlalu lama (timeout). Silakan coba kembali.';
                    break;
            }
            updateGpsUIState('error', errorMsg);
        },
        options
    );
}

// ----------------------------------------------------
// BUTTON EVALUATION & DOUBLE SUBMIT PROTECTION
// ----------------------------------------------------

function evaluateSubmitButtons() {
    const btnCheckIn = document.getElementById('btn-check-in');
    const btnCheckOut = document.getElementById('btn-check-out');

    const checkInSpan = btnCheckIn ? btnCheckIn.querySelector('span') : null;
    const checkOutSpan = btnCheckOut ? btnCheckOut.querySelector('span') : null;

    if (isGpsValid && isSelfieConfirmed) {
        if (btnCheckIn) {
            btnCheckIn.disabled = false;
            btnCheckIn.classList.remove('opacity-50', 'cursor-not-allowed');
            if (checkInSpan) checkInSpan.innerText = 'Absen Masuk Sekarang';
        }
        if (btnCheckOut) {
            btnCheckOut.disabled = false;
            btnCheckOut.classList.remove('opacity-50', 'cursor-not-allowed');
            if (checkOutSpan) checkOutSpan.innerText = 'Absen Keluar Sekarang';
        }
    } else {
        let reason = '';
        if (!isGpsValid && !isSelfieConfirmed) {
            reason = '(Mendeteksi Lokasi & Ambil Selfie terlebih dahulu)';
        } else if (!isGpsValid) {
            reason = '(Lokasi GPS Tidak Valid / Di Luar Radius)';
        } else {
            reason = '(Ambil & Gunakan Foto Selfie terlebih dahulu)';
        }

        if (btnCheckIn) {
            btnCheckIn.disabled = true;
            btnCheckIn.classList.add('opacity-50', 'cursor-not-allowed');
            if (checkInSpan) checkInSpan.innerText = 'Absen Masuk ' + reason;
        }
        if (btnCheckOut) {
            btnCheckOut.disabled = true;
            btnCheckOut.classList.add('opacity-50', 'cursor-not-allowed');
            if (checkOutSpan) checkOutSpan.innerText = 'Absen Keluar ' + reason;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    detectGPSLocation();

    const formCheckIn = document.getElementById('form-check-in');
    if (formCheckIn) {
        formCheckIn.addEventListener('submit', function() {
            const btn = document.getElementById('btn-check-in');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                const span = btn.querySelector('span');
                if (span) span.innerText = 'Memproses Absen Masuk...';
            }
        });
    }

    const formCheckOut = document.getElementById('form-check-out');
    if (formCheckOut) {
        formCheckOut.addEventListener('submit', function() {
            const btn = document.getElementById('btn-check-out');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                const span = btn.querySelector('span');
                if (span) span.innerText = 'Memproses Absen Keluar...';
            }
        });
    }
});

// Clean up camera stream on page unload
window.addEventListener('beforeunload', function() {
    stopCameraStream();
});
</script>
@endif
@endsection
