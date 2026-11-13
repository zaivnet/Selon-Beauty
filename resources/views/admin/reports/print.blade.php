<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Kehadiran · {{ $branding['company_name'] }} — {{ $branding['product_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10px !important;
                padding: 0 !important;
            }
            .page-break {
                page-break-before: always;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            thead {
                display: table-header-group !important;
            }
            tr {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 5px 6px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 md:p-8 antialiased min-h-screen">

    <!-- Action Bar (Hidden when printing) -->
    <div class="max-w-7xl mx-auto mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 no-print">
        <a href="{{ route('admin.reports.attendance', $reportData['filters']) }}" class="px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-extrabold rounded-xl text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition-all flex items-center justify-center sm:justify-start gap-1.5 min-h-[44px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Aplikasi</span>
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer min-h-[44px] ui-btn ui-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak Dokumen (Print / Save PDF)</span>
        </button>
    </div>

    <!-- Main Printable Document Container -->
    <div class="max-w-7xl mx-auto bg-white text-slate-900 p-4 sm:p-8 rounded-2xl shadow-sm border border-slate-200 space-y-6 print:border-none print:shadow-none print:p-0">

        <!-- Document Header -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between border-b-2 border-slate-900 pb-4 gap-4">
            <div class="flex items-center gap-3">
                @if(!empty($branding['app_logo_url']))
                    <img src="{{ $branding['app_logo_url'] }}" alt="{{ $branding['company_name'] }}" class="h-12 w-12 object-contain rounded-lg">
                @endif
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-950 ui-page-header">{{ $branding['company_name'] }}</h1>
                    <h2 class="text-xs sm:text-sm font-black text-rose-700 uppercase tracking-widest mt-0.5">Laporan Kehadiran Karyawan · {{ $branding['product_name'] }}</h2>
                </div>
            </div>
            <div class="sm:text-right text-xs space-y-0.5 text-slate-600 font-medium">
                <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d M Y') }}</p>
                <p><strong>Outlet:</strong> {{ $selectedOutlet?->name ?? 'Semua Outlet' }}</p>
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
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 text-center text-xs">
            <div class="p-2.5 bg-slate-50 border border-slate-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-slate-500 block uppercase">Hari Kerja</span>
                <span class="text-base font-black text-slate-900 font-mono">{{ $gSum['scheduled_work_days'] }}</span>
            </div>
            <div class="p-2.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-emerald-700 block uppercase">Hadir</span>
                <span class="text-base font-black text-emerald-900 font-mono">{{ $gSum['present_count'] }}</span>
            </div>
            <div class="p-2.5 bg-amber-50 border border-amber-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-amber-700 block uppercase">Libur</span>
                <span class="text-base font-black text-amber-900 font-mono">{{ $gSum['holiday_count'] ?? 0 }}</span>
            </div>
            <div class="p-2.5 bg-slate-900 border border-slate-800 rounded-xl text-white flex flex-col justify-center">
                <span class="text-[9px] font-black text-slate-300 block uppercase">Rate</span>
                <span class="text-base font-black font-mono">{{ number_format((float) ($gSum['attendance_rate'] ?? 0), 1) }}%</span>
            </div>
            <div class="p-2.5 bg-rose-50 border border-rose-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-rose-700 block uppercase">Tdk Hadir</span>
                <span class="text-base font-black text-rose-900 font-mono">{{ $gSum['absent_count'] }}</span>
            </div>
            <div class="p-2.5 bg-indigo-50 border border-indigo-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-indigo-700 block uppercase">Izin/Skt/Cuti</span>
                <span class="text-base font-black text-indigo-900 font-mono">{{ $gSum['permission_count'] + $gSum['sick_count'] + $gSum['leave_count'] }}</span>
            </div>
            <div class="p-2.5 bg-blue-50 border border-blue-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-blue-700 block uppercase">Worked</span>
                <span class="text-xs font-black text-blue-900 font-mono">{{ $wHours }}j {{ $wMins }}m</span>
            </div>
            <div class="p-2.5 bg-pink-50 border border-pink-300 rounded-xl flex flex-col justify-center">
                <span class="text-[9px] font-black text-pink-700 block uppercase leading-tight">Apprv Lembur</span>
                <span class="text-xs font-black text-pink-900 font-mono mt-0.5">{{ $oHours }}j {{ $oMins }}m</span>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2 flex justify-between items-center">
                <span>Rincian Presensi Harian ({{ count($reportData['detail_rows']) }} Data)</span>
                <span class="text-[10px] font-normal text-slate-500 sm:hidden no-print">Geser tabel &rarr;</span>
            </h3>
            <div class="overflow-x-auto print:overflow-visible -mx-4 sm:mx-0 px-4 sm:px-0 ui-table-container">
                <table class="w-full min-w-max print:min-w-full text-left text-xs border border-slate-300 ui-table">
                    <thead class="bg-slate-100 text-slate-800 uppercase font-black text-[9px]">
                    <tr>
                        <th class="p-2 border border-slate-300">Tanggal</th>
                        <th class="p-2 border border-slate-300">NIP / Nama Karyawan</th>
                        <th class="p-2 border border-slate-300">Jabatan</th>
                        <th class="p-2 border border-slate-300">Outlet Kerja</th>
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
                            <td class="p-2 border border-slate-300 whitespace-nowrap">
                                <span class="font-bold block">{{ $row['work_outlet']?->name ?? '-' }}</span>
                                @if(!empty($row['is_temporary_assignment']))
                                    <span class="block text-[8px] font-black text-indigo-700">[PENUGASAN OUTLET]</span>
                                    <span class="block text-[8px] text-slate-500 font-normal">Home: {{ $row['historical_home_outlet']?->name ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="p-2 border border-slate-300 whitespace-nowrap">{{ $row['shift']?->name ?? ($row['effective_schedule']['label'] ?? strtoupper($row['schedule']?->schedule_type ?? '-')) }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $checkInAt ? $checkInAt->format('H:i') : '-' }}</td>
                            <td class="p-2 border border-slate-300 font-mono">{{ $checkOutAt ? $checkOutAt->format('H:i') : '-' }}</td>
                            <td class="p-2 border border-slate-300 font-black">{{ $row['status_label'] }}</td>
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
        </div>

        <!-- Document Footer / Signatures -->
        <div class="pt-6 grid grid-cols-2 gap-8 text-center text-xs font-semibold border-t border-slate-200">
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

        <!-- System Footer Disclaimer -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400">
            <span>Dokumen ini dicetak dari sistem Absensi Zaiv untuk keperluan rekap operasional.</span>
            <span>Halaman dicetak secara otomatis dari peramban.</span>
        </div>

    </div>

</body>
</html>
