@php
    $data = $item['data'] ?? [];
    $facts = [];
    $dateText = fn ($value) => $value ? \Carbon\Carbon::parse($value)->locale('id')->isoFormat('D MMM YYYY') : null;
    $timeText = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    $durationText = function ($minutes) {
        $minutes = (int) $minutes;
        return $minutes >= 60 ? intdiv($minutes, 60).'j '.($minutes % 60).'m' : $minutes.'m';
    };

    switch ($item['category']) {
        case 'pending_check_in':
        case 'absent':
            $facts = array_filter([
                ['label' => 'Shift', 'value' => $data['shift_name'] ?? null],
                ['label' => 'Jadwal', 'value' => ($data['shift_start'] ?? null) && ($data['shift_end'] ?? null) ? substr($data['shift_start'], 0, 5).'–'.substr($data['shift_end'], 0, 5) : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'late':
            $facts = array_filter([
                ['label' => 'Shift', 'value' => $data['shift_name'] ?? null],
                ['label' => 'Masuk', 'value' => $timeText($data['check_in_at'] ?? null)],
                ['label' => 'Terlambat', 'value' => isset($data['late_minutes']) ? $durationText($data['late_minutes']) : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'missing_checkout':
            $facts = array_filter([
                ['label' => 'Work date', 'value' => $dateText($data['work_date'] ?? null)],
                ['label' => 'Shift', 'value' => $data['shift_name'] ?? null],
                ['label' => 'Akhir shift', 'value' => isset($data['shift_end']) ? substr($data['shift_end'], 0, 5) : null],
                ['label' => 'Overdue', 'value' => isset($data['overdue_minutes']) && $data['overdue_minutes'] > 0 ? $durationText($data['overdue_minutes']) : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'attendance_needs_review':
            $facts = collect($data['issues'] ?? [])->take(2)->map(fn ($issue) => ['label' => 'Review', 'value' => $issue['label'] ?? null])->filter(fn ($fact) => filled($fact['value']))->all();
            break;
        case 'overtime_active':
            $facts = array_filter([
                ['label' => 'Mulai', 'value' => $timeText($data['start_at'] ?? null)],
                ['label' => 'Berjalan', 'value' => isset($data['elapsed_minutes']) ? $durationText($data['elapsed_minutes']) : null],
                ['label' => 'Disetujui', 'value' => isset($data['approved_minutes']) ? $durationText($data['approved_minutes']) : null],
                ['label' => 'Sisa', 'value' => isset($data['remaining_minutes']) ? $durationText($data['remaining_minutes']) : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'overtime_approved_not_started':
            $facts = array_filter([
                ['label' => 'Tanggal', 'value' => $dateText($data['work_date'] ?? null)],
                ['label' => 'Disetujui', 'value' => isset($data['approved_minutes']) ? $durationText($data['approved_minutes']) : null],
                ['label' => 'Attendance', 'value' => $data['attendance_status'] ?? null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'pending_leave':
            $start = $dateText($data['start_date'] ?? null);
            $end = $dateText($data['end_date'] ?? null);
            $facts = array_filter([
                ['label' => 'Periode', 'value' => $start && $end ? $start.' – '.$end : null],
                ['label' => 'Menunggu', 'value' => isset($data['age_hours']) ? $data['age_hours'].' jam' : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'pending_overtime':
            $facts = array_filter([
                ['label' => 'Tanggal', 'value' => $dateText($data['work_date'] ?? null)],
                ['label' => 'Durasi', 'value' => isset($data['requested_minutes']) ? $durationText($data['requested_minutes']) : null],
                ['label' => 'Menunggu', 'value' => isset($data['age_hours']) ? $data['age_hours'].' jam' : null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'schedule_override':
            $facts = array_filter([
                ['label' => 'Override', 'value' => strtoupper($data['override_type'] ?? '')],
                ['label' => 'Shift reguler', 'value' => $data['regular_shift'] ?? ($data['regular_schedule'] ?? null)],
                ['label' => 'Shift efektif', 'value' => $data['effective_shift'] ?? (($data['override_type'] ?? null) === 'off' ? 'OFF' : null)],
                ['label' => 'Alasan', 'value' => $data['reason'] ?? null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'recent_correction':
            $changed = collect($data['changed_fields'] ?? [])->map(fn ($field) => str_replace('_', ' ', $field))->join(', ');
            $facts = array_filter([
                ['label' => 'Oleh', 'value' => $data['actor'] ?? null],
                ['label' => 'Waktu', 'value' => $timeText($data['time'] ?? null)],
                ['label' => 'Diubah', 'value' => $changed ?: null],
                ['label' => 'Alasan', 'value' => $data['reason'] ?? null],
            ], fn ($fact) => filled($fact['value']));
            break;
        case 'backup_scheduler_issue':
            $facts = array_filter([
                ['label' => 'Status', 'value' => isset($data['latest_status']) ? strtoupper($data['latest_status']) : null],
                ['label' => 'Berhasil terakhir', 'value' => isset($data['last_successful_at']) ? $dateText($data['last_successful_at']).' '.$timeText($data['last_successful_at']) : null],
            ], fn ($fact) => filled($fact['value']));
            break;
    }
@endphp

@if($facts !== [])
    <dl class="flex min-w-0 flex-wrap gap-x-4 gap-y-2">
        @foreach($facts as $fact)
            <div class="min-w-0"><dt class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $fact['label'] }}</dt><dd class="mt-0.5 break-words font-mono text-[11px] font-bold text-slate-700">{{ $fact['value'] }}</dd></div>
        @endforeach
    </dl>
@endif
