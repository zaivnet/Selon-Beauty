@extends('layouts.employee')

@section('title', 'Pengajuan Lembur')

@section('content')
<div class="space-y-5">

    <!-- Flash Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Gagal Mengirim Pengajuan Lembur:</p>
            <ul class="list-disc list-inside space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex bg-slate-200/80 p-1 rounded-xl gap-1 text-xs font-bold">
        <a href="{{ route('employee.leave-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.leave-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Izin / Sakit / Cuti
        </a>
        <a href="{{ route('employee.overtime-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.overtime-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Lembur
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Pengajuan Lembur</h2>
        <p class="text-xs text-slate-500 font-medium">Buat pengajuan kerja lembur dan pantau durasi persetujuan dari Owner/Admin.</p>
    </div>

    <!-- Form Buat Pengajuan Lembur Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                +
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Ajukan Lembur Baru</h3>
        </div>

        <form action="{{ route('employee.overtime-requests.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <!-- Tanggal Kerja -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Kerja <span class="text-rose-600">*</span></label>
                <select name="work_date" id="work_date_select" required onchange="updateAttendanceContext()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <option value="">-- Pilih Tanggal Kerja (Jadwal WORK) --</option>
                    @foreach($availableSchedules as $sch)
                        @php
                            $dateStr = $sch->work_date->format('Y-m-d');
                            $shiftName = $sch->shift ? $sch->shift->name . ' (' . substr($sch->shift->start_time, 0, 5) . ' - ' . substr($sch->shift->end_time, 0, 5) . ')' : 'Jadwal Kerja';
                        @endphp
                        <option value="{{ $dateStr }}" {{ old('work_date') === $dateStr ? 'selected' : '' }}>
                            {{ $sch->work_date->translatedFormat('d F Y') }} — {{ $shiftName }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Lembur hanya dapat diajukan pada tanggal dengan jadwal kerja aktif (WORK).</p>
            </div>

            <!-- Attendance Context Preview Card -->
            <div id="attendance_context_card" class="bg-rose-50/60 border border-rose-100 rounded-xl p-3 space-y-2 hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-rose-800 uppercase tracking-wider">Konteks Absensi (Referensi)</span>
                    <span id="ctx_shift" class="text-[10px] font-bold text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded-full"></span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] font-semibold text-slate-700">
                    <div>Check-in: <span id="ctx_checkin" class="font-bold text-slate-900">-</span></div>
                    <div>Check-out: <span id="ctx_checkout" class="font-bold text-slate-900">-</span></div>
                    <div>Worked Time: <span id="ctx_worked" class="font-bold text-slate-900">-</span></div>
                    <div>Candidate Lembur: <span id="ctx_candidate" class="font-bold text-rose-600">-</span></div>
                </div>
                <p class="text-[10px] text-slate-500 italic">* Data absensi di atas hanya sebagai referensi dan tidak otomatis menyetujui pengajuan.</p>
            </div>

            <!-- Durasi Lembur (menit) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Durasi Lembur (Menit) <span class="text-rose-600">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="requested_minutes" id="requested_minutes" value="{{ old('requested_minutes', 60) }}" min="1" max="1440" required placeholder="Contoh: 60" class="flex-1 w-full min-w-0 max-w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-extrabold text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[44px]">
                    <span class="text-xs font-bold text-slate-500 shrink-0">Menit</span>
                </div>
                <!-- Quick Preset Buttons -->
                <div class="flex flex-wrap gap-2 mt-2">
                    <button type="button" onclick="setPreset(30)" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition-colors min-h-[36px]">30 Menit</button>
                    <button type="button" onclick="setPreset(60)" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition-colors min-h-[36px]">60 Menit (1 Jam)</button>
                    <button type="button" onclick="setPreset(90)" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition-colors min-h-[36px]">90 Menit (1.5 Jam)</button>
                    <button type="button" onclick="setPreset(120)" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition-colors min-h-[36px]">120 Menit (2 Jam)</button>
                </div>
            </div>

            <!-- Alasan -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Alasan Lembur <span class="text-rose-600">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Contoh: Menyelesaikan pekerjaan setelah jam operasional..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">{{ old('reason') }}</textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold rounded-xl shadow-md shadow-rose-600/20 transition-all text-xs flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Ajukan Lembur</span>
            </button>
        </form>
    </div>

    <!-- History Pengajuan Lembur Saya Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-slate-900">Riwayat Lembur Saya</h3>
            <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">{{ count($requests) }} Pengajuan</span>
        </div>

        @if(count($requests) === 0)
            <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <p class="text-xs font-bold text-slate-700">Belum Ada Riwayat Lembur</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Anda belum pernah membuat pengajuan lembur.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($requests as $req)
                    @php
                        $dateKey = $req->work_date->format('Y-m-d');
                        $att = $attendances[$dateKey] ?? null;
                        $sch = $schedules[$dateKey] ?? null;
                    @endphp
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/30 hover:bg-white transition-all space-y-2.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-extrabold text-slate-900">{{ $req->work_date->translatedFormat('l, d F Y') }}</span>
                                @if($sch && $sch->shift)
                                    <p class="text-[11px] text-slate-500 font-medium">Shift: {{ $sch->shift->name }} ({{ substr($sch->shift->start_time, 0, 5) }} - {{ substr($sch->shift->end_time, 0, 5) }})</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        <!-- Duration Info Grid -->
                        <div class="grid grid-cols-2 gap-2 bg-white p-2.5 rounded-lg border border-slate-100 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold block uppercase tracking-wider">Requested</span>
                                <span class="font-extrabold text-slate-800">{{ $req->formatted_requested_duration }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold block uppercase tracking-wider">Approved</span>
                                @if($req->status === 'approved')
                                    <span class="font-extrabold text-emerald-700">{{ $req->formatted_approved_duration }}</span>
                                @else
                                    <span class="font-bold text-slate-400">-</span>
                                @endif
                            </div>
                        </div>

                        <!-- Attendance Info if available -->
                        @if($att)
                            <div class="text-[11px] text-slate-600 bg-rose-50/40 p-2 rounded-lg border border-rose-100/60 flex items-center justify-between">
                                <span>Check-in: <strong>{{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }}</strong> | Check-out: <strong>{{ $att->check_out_at ? $att->check_out_at->format('H:i') : '-' }}</strong></span>
                                <span>Candidate: <strong class="text-rose-700">{{ $att->overtime_minutes }}m</strong></span>
                            </div>
                        @endif

                        <!-- Reason -->
                        <div class="text-xs">
                            <span class="font-bold text-slate-700">Alasan: </span>
                            <span class="text-slate-600 font-medium">{{ $req->reason }}</span>
                        </div>

                        <!-- Reviewer Note -->
                        @if($req->reviewer_note)
                            <div class="text-xs bg-slate-100 p-2 rounded-lg border border-slate-200">
                                <span class="font-bold text-slate-700">Catatan Reviewer ({{ $req->reviewer?->name ?? 'Admin' }}): </span>
                                <span class="text-slate-600 font-medium">{{ $req->reviewer_note }}</span>
                            </div>
                        @endif

                        <!-- Action: Cancel Pending Request -->
                        @if($req->status === 'pending')
                            <div class="pt-1 flex justify-end">
                                <form action="{{ route('employee.overtime-requests.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan lembur ini?')">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition-all cursor-pointer">
                                        Batalkan Pengajuan
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
    const availableAttendancesData = @json($availableAttendances);
    const availableSchedulesData = @json($availableSchedules->keyBy(fn ($s) => $s->work_date->format('Y-m-d')));

    function setPreset(mins) {
        document.getElementById('requested_minutes').value = mins;
    }

    function updateAttendanceContext() {
        const selectedDate = document.getElementById('work_date_select').value;
        const card = document.getElementById('attendance_context_card');
        
        if (!selectedDate) {
            card.classList.add('hidden');
            return;
        }

        const sch = availableSchedulesData[selectedDate];
        const att = availableAttendancesData[selectedDate];

        if (sch || att) {
            card.classList.remove('hidden');
            
            // Shift
            if (sch && sch.shift) {
                const start = sch.shift.start_time.substring(0, 5);
                const end = sch.shift.end_time.substring(0, 5);
                document.getElementById('ctx_shift').innerText = `${sch.shift.name} (${start} - ${end})`;
            } else {
                document.getElementById('ctx_shift').innerText = 'Jadwal Kerja';
            }

            // Attendance details
            if (att) {
                document.getElementById('ctx_checkin').innerText = att.check_in_at ? att.check_in_at.substring(11, 16) : '-';
                document.getElementById('ctx_checkout').innerText = att.check_out_at ? att.check_out_at.substring(11, 16) : '-';
                
                const workedMins = att.worked_minutes || 0;
                const h = Math.floor(workedMins / 60);
                const m = workedMins % 60;
                document.getElementById('ctx_worked').innerText = `${h}j ${m}m`;

                document.getElementById('ctx_candidate').innerText = `${att.overtime_minutes || 0} menit`;
            } else {
                document.getElementById('ctx_checkin').innerText = 'Belum Check-in';
                document.getElementById('ctx_checkout').innerText = 'Belum Check-out';
                document.getElementById('ctx_worked').innerText = '0j 0m';
                document.getElementById('ctx_candidate').innerText = '0 menit';
            }
        } else {
            card.classList.add('hidden');
        }
    }

    // Trigger on load if date preselected
    document.addEventListener('DOMContentLoaded', () => {
        updateAttendanceContext();
    });
</script>
@endsection
