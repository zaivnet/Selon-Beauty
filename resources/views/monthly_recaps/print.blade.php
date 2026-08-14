<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap {{ $recap['employee']->full_name }} — {{ $recap['period']['label'] }}</title>
    <style>
        *{box-sizing:border-box} body{margin:0;background:#fff;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:1.35} .page{max-width:1400px;margin:0 auto;padding:24px}.header{display:flex;justify-content:space-between;gap:24px;padding-bottom:16px;border-bottom:2px solid #0f172a}.brand{display:flex;align-items:center;gap:12px}.logo{width:42px;height:42px;object-fit:contain}.brand-mark{width:42px;height:42px;display:grid;place-items:center;border:1px solid #cbd5e1;font-weight:800}.title{margin:0;font-size:18px}.muted{color:#64748b}.meta{text-align:right}.readiness{display:inline-block;margin-top:6px;padding:4px 8px;border:1px solid;font-weight:800}.ready{color:#047857;border-color:#6ee7b7;background:#ecfdf5}.review{color:#92400e;border-color:#fcd34d;background:#fffbeb}.notice{margin-top:12px;padding:9px 12px;border:1px solid #fcd34d;background:#fffbeb;color:#78350f;font-weight:700}.metrics{display:grid;grid-template-columns:repeat(8,1fr);border:1px solid #cbd5e1;margin:14px 0}.metric{padding:9px;border-right:1px solid #e2e8f0}.metric:last-child{border-right:0}.metric b{display:block;margin-top:3px;font-family:Consolas,monospace;font-size:14px}.section-title{margin:15px 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:.06em}table{width:100%;border-collapse:collapse;table-layout:auto}thead{display:table-header-group}th{background:#f1f5f9;text-align:left;font-size:8px;text-transform:uppercase;letter-spacing:.05em}th,td{padding:5px;border:1px solid #cbd5e1;vertical-align:top}tr{break-inside:avoid;page-break-inside:avoid}.mono{font-family:Consolas,monospace;white-space:nowrap}.badge{font-weight:800}.footer{margin-top:14px;padding-top:8px;border-top:1px solid #cbd5e1;color:#64748b;display:flex;justify-content:space-between;gap:20px}@page{size:A4 landscape;margin:10mm}@media print{.page{max-width:none;padding:0}.no-print{display:none!important}}
    </style>
</head>
<body>
@php
    $summary = $recap['summary'];
    $duration = static function (int $minutes): string { $h = intdiv(max(0, $minutes), 60); $m = max(0, $minutes) % 60; return trim(($h ? $h.'j ' : '').($m ? $m.'m' : ($h ? '' : '0m'))); };
    $time = static fn ($value): string => $value ? ($value instanceof \Carbon\CarbonInterface ? $value : \Carbon\Carbon::parse($value))->format('H:i') : '—';
@endphp
<main class="page">
    <header class="header">
        <div class="brand">
            @if($branding['app_logo_url'])<img class="logo" src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['app_name'] }}">@else<div class="brand-mark">{{ strtoupper(substr($branding['app_name'], 0, 2)) }}</div>@endif
            <div><h1 class="title">Rekap Kehadiran Bulanan</h1><div class="muted">{{ $branding['app_name'] }} · Data kehadiran operasional</div></div>
        </div>
        <div class="meta"><strong>{{ $recap['employee']->full_name }}</strong><div>{{ $recap['employee']->employee_code }} · {{ $recap['employee']->jobTitle?->name ?? 'Karyawan' }}</div><div class="muted">Periode {{ $recap['period']['label'] }}</div><span class="readiness {{ $summary['readiness_status'] === 'READY' ? 'ready' : 'review' }}">{{ $summary['readiness_label'] }}</span></div>
    </header>

    @if($summary['review_required_count'] > 0)<div class="notice">Perlu pemeriksaan: {{ $summary['review_required_count'] }} tanggal memiliki data yang belum lengkap. Detail masalah ditandai pada tabel.</div>@endif

    <section class="metrics">
        @foreach([['Hari Kerja',$summary['effective_work_days']],['Hadir',$summary['present_days']],['Terlambat',$summary['late_days']],['Tidak Hadir',$summary['absent_days']],['Izin / Sakit / Cuti',$summary['permission_days'].' / '.$summary['sick_days'].' / '.$summary['leave_days']],['Jam Reguler',$duration($summary['regular_worked_minutes'])],['Lembur Credited',$duration($summary['overtime_credited_minutes'])],['Attendance Rate',number_format($summary['attendance_rate'],1).'%']] as [$label,$value])<div class="metric"><span class="muted">{{ $label }}</span><b>{{ $value }}</b></div>@endforeach
    </section>

    <h2 class="section-title">Rincian Harian</h2>
    <table>
        <thead><tr><th>Tanggal</th><th>Jadwal / Shift</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Late</th><th>Pulang Cepat</th><th>Reguler</th><th>Leave</th><th>OT Req.</th><th>OT Appr.</th><th>OT Mulai</th><th>OT Selesai</th><th>OT Actual</th><th>OT Credited</th><th>Status Sesi</th><th>Catatan</th></tr></thead>
        <tbody>
        @foreach($recap['daily'] as $day)
            <tr><td><strong>{{ $day['date']->translatedFormat('d M Y') }}</strong><br><span class="muted">{{ $day['day_name'] }}</span></td><td>{{ $day['effective_schedule_label'] }}<br><span class="muted">{{ $day['shift']?->name ?? ($day['holiday_name'] ?: 'Tanpa shift') }}{{ $day['has_override'] ? ' · Override' : '' }}</span></td><td class="badge">{{ $day['status_label'] }}</td><td class="mono">{{ $time($day['check_in_at']) }}</td><td class="mono">{{ $time($day['check_out_at']) }}</td><td class="mono">{{ $day['late_minutes'] }}m</td><td class="mono">{{ $day['early_leave_minutes'] }}m</td><td class="mono">{{ $duration($day['regular_worked_minutes']) }}</td><td>{{ $day['leave_label'] ?: '—' }}</td><td class="mono">{{ $duration($day['overtime_requested_minutes']) }}</td><td class="mono">{{ $duration($day['overtime_approved_minutes']) }}</td><td class="mono">{{ $time($day['overtime_start_at']) }}</td><td class="mono">{{ $time($day['overtime_finish_at']) }}</td><td class="mono">{{ $duration($day['overtime_actual_minutes']) }}</td><td class="mono">{{ $duration($day['overtime_credited_minutes']) }}</td><td>{{ $day['overtime_session_status'] ? strtoupper(str_replace('_', ' ', $day['overtime_session_status'])) : '—' }}</td><td>@if($day['is_corrected'])Dikoreksi Admin<br>@endif @foreach($day['review_issues'] as $issue)<strong>{{ $issue['label'] }}</strong><br>@endforeach</td></tr>
        @endforeach
        </tbody>
    </table>

    <footer class="footer"><span>Digenerate {{ $generatedAt }} · Browser Print / Save PDF</span><strong>Dokumen ini bukan slip atau perhitungan gaji.</strong></footer>
</main>
</body>
</html>
