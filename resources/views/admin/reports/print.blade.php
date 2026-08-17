<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Kehadiran Karyawan - {{ $branding['app_name'] ?? 'Absensi' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11px !important;
            }
            .page-break {
                page-break-before: always;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-4 md:p-8 antialiased min-h-screen">

    <!-- Action Bar (Hidden when printing) -->
    <div class="max-w-6xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('admin.reports.attendance', $reportData['filters']) }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-extrabold rounded-xl text-xs transition-all flex items-center gap-1">
            &larr; Kembali ke Aplikasi
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak Dokumen (Print / Save PDF)</span>
        </button>
    </div>

    <!-- Main Printable Document Container -->
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 space-y-6">

        <!-- Document Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ $branding['company_name'] ?? ($branding['app_name'] ?? 'Aplikasi Absensi') }}</h1>
                <h2 class="text-sm font-extrabold text-rose-700 uppercase tracking-widest mt-0.5">Laporan Kehadiran Karyawan</h2>
            </div>
            <div class="text-right text-xs space-y-0.5 text-slate-600 font-medium">
                <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d M Y') }}</p>
                <p><strong>Dicetak Pada:</strong> {{ $printedAt }}</p>
            </div>
        </div>

        <!-- Global Summary Grid -->
        @php
            $gSum = $reportData['global_summary'];
            $wHours = (int) floor($gSum['total_worked_minutes'] / 60);
            $wMins = $gSum['total_worked_minutes'] % 60;

            $oHours = (int) floor($gSum['total_approved_overtime_minutes'] / 60);
            $oMins = $gSum['total_approved_overtime_minutes'] % 60;
        @endphp
        <div class="grid grid-cols-8 gap-2 text-center text-xs">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <span class="text-[10px] font-bold text-slate-500 block uppercase">Hari Kerja</span>
                <span class="text-base font-black text-slate-900">{{ $gSum['scheduled_work_days'] }}</span>
            </div>
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <span class="text-[10px] font-bold text-emerald-700 block uppercase">Hadir</span>
                <span class="text-base font-black text-emerald-900">{{ $gSum['present_count'] }}</span>
            </div>
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                <span class="text-[10px] font-bold text-amber-700 block uppercase">Libur</span>
                <span class="text-base font-black text-amber-900">{{ $gSum['holiday_count'] ?? 0 }}</span>
            </div>
            <div class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-white">
                <span class="text-[10px] font-bold text-slate-300 block uppercase">Rate</span>
                <span class="text-base font-black">{{ number_format((float) ($gSum['attendance_rate'] ?? 0), 1) }}%</span>
            </div>
            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl">
                <span class="text-[10px] font-bold text-rose-700 block uppercase">Tidak Hadir</span>
                <span class="text-base font-black text-rose-900">{{ $gSum['absent_count'] }}</span>
            </div>
            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
                <span class="text-[10px] font-bold text-indigo-700 block uppercase">Izin/Sakit/Cuti</span>
                <span class="text-base font-black text-indigo-900">{{ $gSum['permission_count'] + $gSum['sick_count'] + $gSum['leave_count'] }}</span>
            </div>
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl">
                <span class="text-[10px] font-bold text-blue-700 block uppercase">Total Worked</span>
                <span class="text-sm font-black text-blue-900 font-mono">{{ $wHours }}j {{ $wMins }}m</span>
            </div>
            <div class="p-3 bg-pink-50 border border-pink-200 rounded-xl">
                <span class="text-[10px] font-bold text-pink-700 block uppercase">Approved Lembur</span>
                <span class="text-sm font-black text-pink-900 font-mono">{{ $oHours }}j {{ $oMins }}m</span>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider mb-2">Rincian Presensi Harian</h3>
            <table class="w-full text-left text-xs border border-slate-300">
                <thead class="bg-slate-100 text-slate-800 uppercase font-extrabold text-[10px]">
                    <tr>
                        <th class="p-2 border border-slate-300">Tanggal</th>
                        <th class="p-2 border border-slate-300">NIP / Nama Karyawan</th>
                        <th class="p-2 border border-slate-300">Jabatan</th>
                        <th class="p-2 border border-slate-300">Shift</th>
                        <th class="p-2 border border-slate-300">Masuk</th>
                        <th class="p-2 border border-slate-300">Pulang</th>
                        <th class="p-2 border border-slate-300">Status</th>
                        <th class="p-2 border border-slate-300">Terlambat</th>
                        <th class="p-2 border border-slate-300">Worked</th>
                        <th class="p-2 border border-slate-300">Lembur Approved</th>
                        <th class="p-2 border border-slate-300">Lembur Actual</th>
                        <th class="p-2 border border-slate-300">Lembur Credited</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-medium">
                    @foreach($reportData['detail_rows'] as $row)
                        @php
                            $rWHours = (int) floor($row['worked_minutes'] / 60);
                            $rWMins = $row['worked_minutes'] % 60;
                            $rowDate = isset($row['date']) && $row['date']
                                ? ($row['date'] instanceof \Carbon\CarbonInterface ? $row['date'] : \Carbon\Carbon::parse($row['date']))
                                : null;
                            $checkInAt = isset($row['check_in_at']) && $row['check_in_at']
                                ? ($row['check_in_at'] instanceof \Carbon\CarbonInterface ? $row['check_in_at'] : \Carbon\Carbon::parse($row['check_in_at']))
                                : null;
                            $checkOutAt = isset($row['check_out_at']) && $row['check_out_at']
                                ? ($row['check_out_at'] instanceof \Carbon\CarbonInterface ? $row['check_out_at'] : \Carbon\Carbon::parse($row['check_out_at']))
                                : null;
                        @endphp
                        <tr>
                            <td class="p-2 border border-slate-300 font-bold whitespace-nowrap">{{ $rowDate ? $rowDate->format('d/m/Y') : '-' }}</td>
                            <td class="p-2 border border-slate-300 font-bold">{{ $row['employee']->full_name }} <span class="font-normal text-[10px] block">({{ $row['employee']->employee_code }})</span></td>
                            <td class="p-2 border border-slate-300">{{ $row['employee']->jobTitle?->name ?? '-' }}</td>
                            <td class="p-2 border border-slate-300 whitespace-nowrap">{{ $row['shift']?->name ?? ($row['effective_schedule']['label'] ?? strtoupper($row['schedule']?->schedule_type ?? '-')) }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $checkInAt ? $checkInAt->format('H:i') : '-' }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}</td>
                            <td class="p-2 border border-slate-300 font-extrabold">{{ $row['status_label'] }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $row['late_minutes'] > 0 ? $row['late_minutes'] . 'm' : '0m' }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $row['worked_minutes'] > 0 ? "{$rWHours}j {$rWMins}m" : '0m' }}</td>
                            <td class="p-2 border border-slate-300 font-mono font-bold">{{ $row['approved_overtime_minutes'] > 0 ? $row['approved_overtime_minutes'] . 'm' : '0m' }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $row['actual_overtime_minutes'] ?? 0 }}m</td>
                            <td class="p-2 border border-slate-300 font-mono font-bold">{{ $row['credited_overtime_minutes'] ?? 0 }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Document Footer / Signatures -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-center text-xs font-semibold">
            <div>
                <p>Dibuat Oleh,</p>
                <div class="h-16"></div>
                <p class="font-bold border-t border-slate-400 max-w-xs mx-auto pt-1">Admin Operasional</p>
            </div>
            <div>
                <p>Disetujui Oleh,</p>
                <div class="h-16"></div>
                <p class="font-bold border-t border-slate-400 max-w-xs mx-auto pt-1">Owner</p>
            </div>
        </div>

    </div>

</body>
</html>
