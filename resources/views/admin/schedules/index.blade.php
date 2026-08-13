@extends('layouts.admin')

@section('title', 'Penjadwalan Kerja Karyawan')
@section('page-title', 'Matriks Penjadwalan Kerja Mingguan')

@section('content')
<div class="space-y-6">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Actions & Week Navigation Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Date Range Navigation -->
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.schedules.index', ['start_date' => $prevWeekDate]) }}" class="p-2 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors text-slate-600 text-xs font-bold flex items-center gap-1">
                &larr; Minggu Lalu
            </a>

            <div class="text-center">
                <span class="text-xs font-extrabold text-slate-900 block">
                    {{ $startDate->locale('id')->isoFormat('D MMMM YYYY') }} — {{ $endDate->locale('id')->isoFormat('D MMMM YYYY') }}
                </span>
                <span class="text-[10px] text-rose-600 font-bold uppercase tracking-wider">Minggu ke-{{ $startDate->weekOfYear }}</span>
            </div>

            <a href="{{ route('admin.schedules.index', ['start_date' => $nextWeekDate]) }}" class="p-2 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors text-slate-600 text-xs font-bold flex items-center gap-1">
                Minggu Depan &rarr;
            </a>

            @if(!$startDate->isSameWeek(now()))
                <a href="{{ route('admin.schedules.index') }}" class="text-xs font-bold text-rose-600 underline">Hari Ini</a>
            @endif
        </div>

        <!-- Right Action Buttons -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'show_copy_preview' => 1]) }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                <span>Salin Minggu Lalu</span>
            </a>

            <button type="button" onclick="openAssignModal('', '{{ date('Y-m-d') }}')" class="px-4 py-2 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold text-xs rounded-xl shadow-xs hover:from-rose-700 hover:to-pink-700 transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Atur Jadwal</span>
            </button>
        </div>
    </div>

    <!-- Copy Week Preview Warning Alert -->
    @if($copyPreview)
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 space-y-3 text-xs">
            <div class="flex items-center justify-between font-bold text-indigo-900">
                <span>Pratinjau Salin Jadwal Minggu Lalu</span>
                <a href="{{ route('admin.schedules.index', ['start_date' => $startDate->format('Y-m-d')]) }}" class="text-rose-600 text-[11px] underline">Tutup Pratinjau</a>
            </div>

            <p class="text-indigo-800">
                Akan menyalin <strong>{{ $copyPreview['total_source_items'] }}</strong> item jadwal dari minggu ({{ $copyPreview['prev_start'] }} s.d. {{ $copyPreview['prev_end'] }}).
                @if($copyPreview['conflict_count'] > 0)
                    <span class="text-rose-700 font-bold block mt-1">⚠ Peringatan: Terdapat {{ $copyPreview['conflict_count'] }} jadwal yang sudah ada pada minggu ini (Bentrokan).</span>
                @endif
            </p>

            <form action="{{ route('admin.schedules.copy-week.execute') }}" method="POST" class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                @csrf
                <input type="hidden" name="target_start_date" value="{{ $startDate->format('Y-m-d') }}">
                
                <label class="flex items-center gap-2 text-indigo-900 font-semibold cursor-pointer">
                    <input type="checkbox" name="overwrite" value="1" class="w-4 h-4 text-indigo-600 rounded">
                    <span>Timpa Jadwal Existing yang Bentrok</span>
                </label>

                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer">
                    Eksekusi Salin Jadwal
                </button>
            </form>
        </div>
    @endif

    <!-- Main Schedule Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">

        @if($employees->isEmpty())
            <!-- Clean Empty State -->
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <p class="text-xs font-bold text-slate-800">Belum Ada Data Karyawan Aktif</p>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Silakan tambahkan data karyawan terlebih dahulu di menu Manajemen Karyawan untuk membuat jadwal kerja.
                </p>
            </div>
        @else
            <!-- Desktop Weekly Matrix Grid (Hidden on Mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider bg-slate-50/60">
                            <th class="p-3 w-48 min-w-[180px]">Karyawan</th>
                            @foreach($weekDays as $day)
                                <th class="p-3 text-center min-w-[110px] {{ $day['is_today'] ? 'bg-rose-50 text-rose-800 border-x border-rose-200' : '' }}">
                                    <div>{{ $day['day_name'] }}</div>
                                    <div class="text-[11px] font-mono font-normal">{{ $day['short_date'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($employees as $emp)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- Employee Info Column -->
                                <td class="p-3 font-bold text-slate-800">
                                    <div class="truncate">{{ $emp->full_name }}</div>
                                    <div class="text-[10px] font-mono text-rose-600 font-normal">{{ $emp->employee_code }} • {{ $emp->jobTitle?->name ?: 'No Job' }}</div>
                                </td>

                                <!-- 7 Days Columns -->
                                @foreach($weekDays as $day)
                                    @php
                                        $key = $emp->id . '_' . $day['date'];
                                        $sch = $scheduleMatrix[$key] ?? null;
                                    @endphp
                                    <td class="p-2 text-center align-middle {{ $day['is_today'] ? 'bg-rose-50/40 border-x border-rose-100' : '' }}">
                                        @if($sch)
                                            @if($sch->schedule_type === 'work' && $sch->shift)
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '{{ $sch->shift_id }}', '{{ e($sch->notes) }}')"
                                                     class="p-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 hover:border-rose-300 hover:shadow-xs text-rose-900 transition-all cursor-pointer space-y-0.5 group">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold font-mono text-[11px] uppercase block">{{ $sch->shift->code }}</span>
                                                        <svg class="w-3 h-3 text-rose-400 group-hover:text-rose-600 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </div>
                                                    <span class="text-[10px] block text-slate-600 font-medium">{{ substr($sch->shift->start_time, 0, 5) }} - {{ substr($sch->shift->end_time, 0, 5) }}</span>
                                                </div>
                                            @elseif($sch->schedule_type === 'off')
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}')"
                                                     class="p-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200/70 hover:border-slate-300 hover:shadow-xs text-slate-700 font-extrabold text-[11px] transition-all cursor-pointer flex items-center justify-between group">
                                                    <span>OFF / LIBUR</span>
                                                    <svg class="w-3 h-3 text-slate-400 group-hover:text-slate-600 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </div>
                                            @elseif($sch->schedule_type === 'holiday')
                                                <div onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}')"
                                                     class="p-2 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 hover:border-amber-300 hover:shadow-xs text-amber-900 font-extrabold text-[11px] transition-all cursor-pointer flex items-center justify-between group">
                                                    <span>HOLIDAY</span>
                                                    <svg class="w-3 h-3 text-amber-500 group-hover:text-amber-700 hidden group-hover:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </div>
                                            @endif
                                        @else
                                            <button type="button" onclick="openAssignModal('{{ $emp->id }}', '{{ $day['date'] }}')" class="w-full py-2 border border-dashed border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 rounded-xl text-[11px] text-slate-400 font-medium transition-colors cursor-pointer">
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
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <span class="font-extrabold text-xs text-slate-900 uppercase tracking-wider">
                                {{ $day['day_name'] }}, {{ $day['short_date'] }}
                            </span>
                            @if($day['is_today'])
                                <span class="px-2 py-0.5 bg-rose-600 text-white font-bold text-[10px] rounded-full">HARI INI</span>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @foreach($employees as $emp)
                                @php
                                    $key = $emp->id . '_' . $day['date'];
                                    $sch = $scheduleMatrix[$key] ?? null;
                                @endphp
                                <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-slate-200 text-xs">
                                    <div>
                                        <h5 class="font-bold text-slate-900">{{ $emp->full_name }}</h5>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ $emp->employee_code }}</span>
                                    </div>

                                    <div>
                                        @if($sch)
                                            @if($sch->schedule_type === 'work' && $sch->shift)
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '{{ $sch->shift_id }}', '{{ e($sch->notes) }}')"
                                                        class="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 font-mono font-extrabold text-[11px] rounded-lg active:scale-95 transition-transform cursor-pointer">
                                                    {{ $sch->shift->code }} ({{ substr($sch->shift->start_time, 0, 5) }}-{{ substr($sch->shift->end_time, 0, 5) }})
                                                </button>
                                            @elseif($sch->schedule_type === 'off')
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}')"
                                                        class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 font-extrabold text-[11px] rounded-lg active:scale-95 transition-transform cursor-pointer">
                                                    OFF / LIBUR
                                                </button>
                                            @elseif($sch->schedule_type === 'holiday')
                                                <button type="button" onclick="openEditModal('{{ $sch->id }}', '{{ $emp->id }}', '{{ $day['date'] }}', '{{ $sch->schedule_type }}', '', '{{ e($sch->notes) }}')"
                                                        class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 font-extrabold text-[11px] rounded-lg active:scale-95 transition-transform cursor-pointer">
                                                    HOLIDAY
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" onclick="openAssignModal('{{ $emp->id }}', '{{ $day['date'] }}')" class="text-rose-600 font-bold text-[11px] underline">
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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal_title" class="text-sm font-extrabold text-slate-900">Atur Jadwal Kerja Karyawan</h3>
            <button type="button" onclick="document.getElementById('modal-assign-schedule').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
        </div>

        <form id="schedule-form" action="{{ route('admin.schedules.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="form_method" value="POST">

            <div>
                <label for="modal_employee_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Karyawan *</label>
                <select name="employee_id" id="modal_employee_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full min-w-0 max-w-full">
                <label for="modal_work_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal *</label>
                <input type="date" name="work_date" id="modal_work_date" required value="{{ date('Y-m-d') }}" class="w-full min-w-0 max-w-full box-border px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 min-h-[44px]">
            </div>

            <div>
                <label for="modal_schedule_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Jadwal *</label>
                <select name="schedule_type" id="modal_schedule_type" required onchange="toggleShiftSelect(this.value)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    <option value="work">Kerja (Masuk Shift)</option>
                    <option value="off">OFF / Libur Karyawan</option>
                    <option value="holiday">Holiday / Hari Libur Toko</option>
                </select>
            </div>

            <div id="container-shift-select">
                <label for="modal_shift_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Shift Kerja *</label>
                <select name="shift_id" id="modal_shift_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    <option value="">-- Pilih Shift Kerja --</option>
                    @foreach($shifts as $sf)
                        <option value="{{ $sf->id }}">{{ $sf->name }} ({{ $sf->code }} • {{ substr($sf->start_time, 0, 5) }}-{{ substr($sf->end_time, 0, 5) }}) {{ !$sf->is_active ? '[NONAKTIF]' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="modal_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Opsional</label>
                <input type="text" name="notes" id="modal_notes" placeholder="Catatan instruksi shift" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <button type="button" id="btn_delete_schedule" class="hidden px-3.5 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl transition-colors cursor-pointer" onclick="confirmDeleteSchedule()">
                    Hapus Jadwal
                </button>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="document.getElementById('modal-assign-schedule').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900">Batal</button>
                    <button type="submit" id="btn_submit_schedule" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
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
    document.getElementById('modal_notes').value = "";
    document.getElementById('btn_delete_schedule').classList.add('hidden');
    document.getElementById('btn_submit_schedule').innerText = "Simpan Jadwal";
    toggleShiftSelect("work");
    document.getElementById('modal-assign-schedule').classList.remove('hidden');
}

function openEditModal(scheduleId, employeeId, dateStr, scheduleType, shiftId, notes) {
    document.getElementById('schedule-form').action = "/admin/schedules/" + scheduleId;
    document.getElementById('form_method').value = "PUT";
    document.getElementById('modal_title').innerText = "Ubah Jadwal Kerja Karyawan";
    document.getElementById('modal_employee_id').value = employeeId;
    document.getElementById('modal_work_date').value = dateStr;
    document.getElementById('modal_schedule_type').value = scheduleType;
    document.getElementById('modal_shift_id').value = shiftId || "";
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
    if (type === 'work') {
        container.classList.remove('hidden');
        shiftSelect.required = true;
    } else {
        container.classList.add('hidden');
        shiftSelect.required = false;
    }
}
</script>
@endsection
