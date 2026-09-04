<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\OutletScopeService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected OutletScopeService $outletScopeService,
    ) {}

    public function attendance(Request $request): View
    {
        $startDate = $request->input('start_date', $request->input('from_date', $request->input('from', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'))));
        $endDate = $request->input('end_date', $request->input('to_date', $request->input('to', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'))));
        $rawEmployeeId = $request->input('employee_id');
        $employeeId = ($rawEmployeeId !== null && $rawEmployeeId !== '' && $rawEmployeeId !== 'all' && $rawEmployeeId !== '0') ? (int) $rawEmployeeId : null;
        $status = $request->input('status', 'all');
        $rawJobTitleId = $request->input('job_title_id');
        $jobTitleId = ($rawJobTitleId !== null && $rawJobTitleId !== '' && $rawJobTitleId !== 'all' && $rawJobTitleId !== '0') ? (int) $rawJobTitleId : null;
        $rawOutletId = $request->input('outlet_id');
        $outletId = ($rawOutletId !== null && $rawOutletId !== '' && $rawOutletId !== 'all' && $rawOutletId !== '0') ? (int) $rawOutletId : null;

        $actor = $request->user();
        $allowedOutletIds = $this->outletScopeService->allowedOutletIds($actor);
        $authorizedOutlets = \App\Models\Outlet::whereIn('id', $allowedOutletIds)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
        $authorizedOutletIds = $authorizedOutlets->pluck('id')->all();

        if ($outletId !== null && ! in_array($outletId, $authorizedOutletIds, true)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Akses outlet ditolak. Anda tidak berwenang melihat laporan untuk outlet ini.');
        }

        $employees = $this->reportService->getReportEmployees($actor, $startDate, $endDate, $outletId);

        // If selected employee does not belong to the authorized period/outlet workforce, clear it safely
        if ($employeeId !== null && ! $employees->contains('id', $employeeId)) {
            $employeeId = null;
        }

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $actor,
            'outlet_id' => $outletId,
        ];

        $reportData = $this->reportService->generateAttendanceReport($filters);

        // Paginate detail rows for Web UI
        $page = (int) $request->input('page', 1);
        $perPage = 25;
        $allRows = collect($reportData['detail_rows']);
        $paginatedRows = new LengthAwarePaginator(
            $allRows->forPage($page, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $jobTitles = JobTitle::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('admin.reports.attendance', [
            'reportData' => $reportData,
            'paginatedRows' => $paginatedRows,
            'employees' => $employees,
            'authorizedOutlets' => $authorizedOutlets,
            'jobTitles' => $jobTitles,
            'filters' => $filters,
        ]);
    }

    public function printView(Request $request): View
    {
        $startDate = $request->input('start_date', $request->input('from_date', $request->input('from', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'))));
        $endDate = $request->input('end_date', $request->input('to_date', $request->input('to', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'))));
        $rawEmployeeId = $request->input('employee_id');
        $employeeId = ($rawEmployeeId !== null && $rawEmployeeId !== '' && $rawEmployeeId !== 'all' && $rawEmployeeId !== '0') ? (int) $rawEmployeeId : null;
        $status = $request->input('status', 'all');
        $rawJobTitleId = $request->input('job_title_id');
        $jobTitleId = ($rawJobTitleId !== null && $rawJobTitleId !== '' && $rawJobTitleId !== 'all' && $rawJobTitleId !== '0') ? (int) $rawJobTitleId : null;
        $rawOutletId = $request->input('outlet_id');
        $outletId = ($rawOutletId !== null && $rawOutletId !== '' && $rawOutletId !== 'all' && $rawOutletId !== '0') ? (int) $rawOutletId : null;

        $actor = $request->user();
        $allowedOutletIds = $this->outletScopeService->allowedOutletIds($actor);
        $authorizedOutlets = \App\Models\Outlet::whereIn('id', $allowedOutletIds)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
        $authorizedOutletIds = $authorizedOutlets->pluck('id')->all();

        if ($outletId !== null && ! in_array($outletId, $authorizedOutletIds, true)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Akses outlet ditolak. Anda tidak berwenang melihat laporan untuk outlet ini.');
        }

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $actor,
            'outlet_id' => $outletId,
        ];

        $reportData = $this->reportService->generateAttendanceReport($filters);
        $selectedOutlet = $outletId ? $authorizedOutlets->firstWhere('id', $outletId) : null;

        return view('admin.reports.print', [
            'reportData' => $reportData,
            'selectedOutlet' => $selectedOutlet,
            'printedAt' => now('Asia/Jakarta')->translatedFormat('d F Y H:i:s').' WIB',
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', $request->input('from_date', $request->input('from', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'))));
        $endDate = $request->input('end_date', $request->input('to_date', $request->input('to', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'))));
        $rawEmployeeId = $request->input('employee_id');
        $employeeId = ($rawEmployeeId !== null && $rawEmployeeId !== '' && $rawEmployeeId !== 'all' && $rawEmployeeId !== '0') ? (int) $rawEmployeeId : null;
        $status = $request->input('status', 'all');
        $rawJobTitleId = $request->input('job_title_id');
        $jobTitleId = ($rawJobTitleId !== null && $rawJobTitleId !== '' && $rawJobTitleId !== 'all' && $rawJobTitleId !== '0') ? (int) $rawJobTitleId : null;
        $rawOutletId = $request->input('outlet_id');
        $outletId = ($rawOutletId !== null && $rawOutletId !== '' && $rawOutletId !== 'all' && $rawOutletId !== '0') ? (int) $rawOutletId : null;

        $actor = $request->user();
        $allowedOutletIds = $this->outletScopeService->allowedOutletIds($actor);
        $authorizedOutlets = \App\Models\Outlet::whereIn('id', $allowedOutletIds)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
        $authorizedOutletIds = $authorizedOutlets->pluck('id')->all();

        if ($outletId !== null && ! in_array($outletId, $authorizedOutletIds, true)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Akses outlet ditolak. Anda tidak berwenang melihat laporan untuk outlet ini.');
        }

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $actor,
            'outlet_id' => $outletId,
        ];

        $reportData = $this->reportService->generateAttendanceReport($filters);
        $filename = "attendance-report-{$startDate}-to-{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($reportData) {
            $file = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility (Requirement 13)
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($file, [
                'Date',
                'Employee Code',
                'Employee Name',
                'Job Title',
                'Home Outlet',
                'Outlet Kerja',
                'Shift',
                'Check In',
                'Check Out',
                'Status',
                'Late Minutes',
                'Worked Minutes',
                'Early Leave Minutes',
                'Approved Overtime Minutes',
                'Actual Overtime Minutes',
                'Credited Overtime Minutes',
                'Outlet Assignment Notice',
            ]);

            // Data Rows
            foreach ($reportData['detail_rows'] as $row) {
                $checkInStr = $row['check_in_at'] ? $row['check_in_at']->format('H:i') : '-';
                $checkOutStr = $row['check_out_at'] ? $row['check_out_at']->format('H:i') : '-';
                $shiftStr = $row['shift'] ? $row['shift']->name.' ('.substr($row['shift']->start_time, 0, 5).'-'.substr($row['shift']->end_time, 0, 5).')' : ($row['schedule']?->schedule_type ?? '-');

                $csvRow = [
                    $row['date_str'],
                    $row['employee']->employee_code,
                    $row['employee']->full_name,
                    $row['employee']->jobTitle?->name ?? '-',
                    $row['historical_home_outlet']?->name ?? '-',
                    $row['work_outlet']?->name ?? '-',
                    $shiftStr,
                    $checkInStr,
                    $checkOutStr,
                    $row['status_label'],
                    $row['late_minutes'],
                    $row['worked_minutes'],
                    $row['early_leave_minutes'],
                    $row['approved_overtime_minutes'],
                    $row['actual_overtime_minutes'],
                    $row['credited_overtime_minutes'],
                    ! empty($row['is_temporary_assignment']) ? 'PENUGASAN OUTLET' : 'REGULER',
                ];

                $sanitizedRow = array_map(function ($val) {
                    if (is_string($val) && preg_match('/^[\=\+\-\@\t\r]/', $val)) {
                        return "'".$val;
                    }

                    return $val;
                }, $csvRow);

                fputcsv($file, $sanitizedRow);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
