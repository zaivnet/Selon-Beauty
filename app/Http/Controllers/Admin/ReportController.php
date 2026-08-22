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
        $startDate = $request->input('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $status = $request->input('status', 'all');
        $jobTitleId = $request->input('job_title_id');
        $outletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $request->user(),
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

        $employeesQuery = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();
        $employeesQuery = $this->outletScopeService->scopeByRequestedOutlet($request->user(), $employeesQuery, $outletId);

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();

        $jobTitles = JobTitle::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('admin.reports.attendance', [
            'reportData' => $reportData,
            'paginatedRows' => $paginatedRows,
            'employees' => $employees,
            'jobTitles' => $jobTitles,
            'filters' => $filters,
        ]);
    }

    public function printView(Request $request): View
    {
        $startDate = $request->input('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $status = $request->input('status', 'all');
        $jobTitleId = $request->input('job_title_id');
        $outletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $request->user(),
            'outlet_id' => $outletId,
        ];

        $reportData = $this->reportService->generateAttendanceReport($filters);

        return view('admin.reports.print', [
            'reportData' => $reportData,
            'printedAt' => now('Asia/Jakarta')->translatedFormat('d F Y H:i:s').' WIB',
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now('Asia/Jakarta')->endOfMonth()->format('Y-m-d'));
        $employeeId = $request->input('employee_id');
        $status = $request->input('status', 'all');
        $jobTitleId = $request->input('job_title_id');
        $outletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'status' => $status,
            'job_title_id' => $jobTitleId,
            'actor' => $request->user(),
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
