<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap {{ $recap['employee']->full_name }} — {{ $recap['period']['label'] }} · {{ $branding['product_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.35;
        }
        .page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #0f172a;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 8px;
        }
        .brand-mark {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-weight: 800;
            background: #f1f5f9;
        }
        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.02em;
        }
        .muted { color: #64748b; }
        .meta { text-align: right; }
        .readiness {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            border: 1px solid;
            border-radius: 6px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.05em;
        }
        .ready { color: #047857; border-color: #6ee7b7; background: #ecfdf5; }
        .review { color: #92400e; border-color: #fcd34d; background: #fffbeb; }
        .notice {
            margin-top: 12px;
            padding: 10px 14px;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            background: #fffbeb;
            color: #78350f;
            font-weight: 700;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            margin: 14px 0;
            background: #ffffff;
        }
        .metric {
            padding: 10px;
            border-right: 1px solid #e2e8f0;
            text-align: center;
        }
        .metric:last-child { border-right: 0; }
        .metric span {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .metric b {
            display: block;
            margin-top: 4px;
            font-family: Consolas, monospace;
            font-size: 14px;
            font-weight: 900;
        }
        .section-title {
            margin: 16px 0 8px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            background: #ffffff;
        }
        thead { display: table-header-group; }
        th {
            background: #f1f5f9;
            text-align: left;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        th, td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .mono {
            font-family: Consolas, monospace;
            white-space: nowrap;
        }
        .badge { font-weight: 800; }
        .footer {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            font-size: 9px;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .page {
                max-width: none !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="antialiased">
@php
    $summary = $recap['summary'];
    $period = $recap['period'];
    $duration = static function (int $minutes): string {
        $h = intdiv(max(0, $minutes), 60);
        $m = max(0, $minutes) % 60;
        return trim(($h ? $h.'j ' : '').($m ? $m.'m' : ($h ? '' : '0m')));
    };
    $time = static fn ($value): string => $value ? ($value instanceof \Carbon\CarbonInterface ? $value : \Carbon\Carbon::parse($value))->format('H:i') : '—';
    $backRoute = auth()->user()?->role === 'employee'
        ? route('employee.monthly-recap.show', ['year' => $period['year'], 'month' => $period['month']])
        : route('admin.monthly-recaps.show', ['employee' => $recap['employee']->id, 'year' => $period['year'], 'month' => $period['month']]);
@endphp

<!-- Action Bar (Hidden when printing) -->
<div class="max-w-7xl mx-auto pt-4 px-4 sm:px-6 mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 no-print">
    <a href="{{ $backRoute }}" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-800 font-extrabold rounded-xl text-xs border border-slate-200 shadow-xs transition-all flex items-center justify-center sm:justify-start gap-1.5 min-h-[44px]">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Kembali ke Aplikasi</span>
    </a>
    <button onclick="window.print()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer min-h-[44px] ui-btn ui-btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Cetak Dokumen (Print / Save PDF)</span>
    </button>
</div>

<main class="page">
    <header class="header">
        <div class="brand">
            @if(!empty($branding['app_logo_url']))
                <img class="logo" src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['company_name'] }}">
            @else
                <div class="brand-mark">{{ strtoupper(substr($branding['company_name'] ?? 'AZ', 0, 2)) }}</div>
            @endif
            <div>
                <h1 class="title">Rekap Kehadiran Bulanan</h1>
                <div class="muted font-bold">{{ $branding['company_name'] }} · {{ $branding['product_name'] }}</div>
            </div>
        </div>
        <div class="meta">
            <strong class="text-sm font-black text-slate-900 block">{{ $recap['employee']->full_name }}</strong>
            <div class="font-bold text-slate-700">{{ $recap['employee']->employee_code }} · {{ $recap['employee']->jobTitle?->name ?? 'Karyawan' }}</div>
            <div class="muted">Periode {{ $recap['period']['label'] }}</div>
            <span class="readiness {{ $summary['readiness_status'] === 'READY' ? 'ready' : 'review' }}">
                {{ $summary['readiness_label'] }}
            </span>
        </div>
    </header>

    @if($summary['review_required_count'] > 0)
        <div class="notice">
            Perlu pemeriksaan: {{ $summary['review_required_count'] }} tanggal memiliki data yang belum lengkap. Detail masalah ditandai pada tabel.
        </div>
    @endif

    <section class="metrics">
        @foreach([
            ['Hari Kerja', $summary['effective_work_days']],
            ['Hadir', $summary['present_days']],
            ['Terlambat', $summary['late_days']],
            ['Tidak Hadir', $summary['absent_days']],
            ['Izin / Sakit / Cuti', $summary['permission_days'].' / '.$summary['sick_days'].' / '.$summary['leave_days']],
            ['Jam Reguler', $duration($summary['regular_worked_minutes'])],
            ['Lembur Credited', $duration($summary['overtime_credited_minutes'])],
            ['Attendance Rate', number_format($summary['attendance_rate'], 1).'%'],
        ] as [$label, $value])
            <div class="metric">
                <span class="muted">{{ $label }}</span>
                <b>{{ $value }}</b>
            </div>
        @endforeach
    </section>

    <h2 class="section-title">Rincian Harian</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jadwal / Shift</th>
                <th>Status</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Late</th>
                <th>Pulang Cepat</th>
                <th>Reguler</th>
                <th>Leave</th>
                <th>OT Req.</th>
                <th>OT Appr.</th>
                <th>OT Mulai</th>
                <th>OT Selesai</th>
                <th>OT Actual</th>
                <th>OT Credited</th>
                <th>Status Sesi</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
        @foreach($recap['daily'] as $day)
            <tr>
                <td>
                    <strong>{{ $day['date']->translatedFormat('d M Y') }}</strong><br>
                    <span class="muted">{{ $day['day_name'] }}</span>
                </td>
                <td>
                    {{ $day['effective_schedule_label'] }}<br>
                    <span class="muted">{{ $day['shift']?->name ?? ($day['holiday_name'] ?: 'Tanpa shift') }}{{ $day['has_override'] ? ' · Override' : '' }}</span>
                </td>
                <td class="badge">{{ $day['status_label'] }}</td>
                <td class="mono font-bold">{{ $time($day['check_in_at']) }}</td>
                <td class="mono font-bold">{{ $time($day['check_out_at']) }}</td>
                <td class="mono">{{ $day['late_minutes'] }}m</td>
                <td class="mono">{{ $day['early_leave_minutes'] }}m</td>
                <td class="mono font-bold">{{ $duration($day['regular_worked_minutes']) }}</td>
                <td>{{ $day['leave_label'] ?: '—' }}</td>
                <td class="mono">{{ $duration($day['overtime_requested_minutes']) }}</td>
                <td class="mono">{{ $duration($day['overtime_approved_minutes']) }}</td>
                <td class="mono">{{ $time($day['overtime_start_at']) }}</td>
                <td class="mono">{{ $time($day['overtime_finish_at']) }}</td>
                <td class="mono">{{ $duration($day['overtime_actual_minutes']) }}</td>
                <td class="mono font-bold">{{ $duration($day['overtime_credited_minutes']) }}</td>
                <td>{{ $day['overtime_session_status'] ? strtoupper(str_replace('_', ' ', $day['overtime_session_status'])) : '—' }}</td>
                <td>
                    @if($day['is_corrected'])
                        <strong>Dikoreksi Admin</strong><br>
                    @endif
                    @foreach($day['review_issues'] as $issue)
                        <strong style="color: #b91c1c;">{{ $issue['label'] }}</strong><br>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <footer class="footer">
        <span>Digenerate {{ $generatedAt }} · Browser Print / Save PDF</span>
        <strong>Dokumen ini bukan slip atau perhitungan gaji.</strong>
    </footer>
</main>
</body>
</html>
