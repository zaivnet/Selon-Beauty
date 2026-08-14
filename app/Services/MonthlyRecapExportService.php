<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyRecapExportService
{
    public function summary(array $recapData, string $filename): StreamedResponse
    {
        return $this->stream($filename, [
            'Employee Code', 'Employee', 'Job Title', 'Period', 'Calendar Days',
            'Effective Work Days', 'Present Days', 'Late Days', 'Late Minutes',
            'Absent Days', 'Permission Days', 'Sick Days', 'Leave Days', 'Holiday Days', 'Off Days',
            'Regular Worked Minutes', 'Overtime Requested Minutes', 'Overtime Approved Minutes',
            'Overtime Actual Minutes', 'Overtime Credited Minutes', 'Attendance Rate', 'Readiness',
        ], function () use ($recapData) {
            foreach ($recapData['recaps'] as $recap) {
                $summary = $recap['summary'];
                yield [
                    $summary['employee_code'], $summary['employee_name'], $summary['job_title'] ?? '-',
                    $summary['period'], $summary['calendar_days'], $summary['effective_work_days'],
                    $summary['present_days'], $summary['late_days'], $summary['total_late_minutes'],
                    $summary['absent_days'], $summary['permission_days'], $summary['sick_days'],
                    $summary['leave_days'], $summary['holiday_days'], $summary['off_days'],
                    $summary['regular_worked_minutes'], $summary['overtime_requested_minutes'],
                    $summary['overtime_approved_minutes'], $summary['overtime_actual_minutes'],
                    $summary['overtime_credited_minutes'], $summary['attendance_rate'],
                    $summary['readiness_status'],
                ];
            }
        });
    }

    public function detail(array $recapData, string $filename): StreamedResponse
    {
        return $this->stream($filename, [
            'Employee Code', 'Employee', 'Job Title', 'Period', 'Date', 'Day',
            'Effective Schedule', 'Schedule Source', 'Shift', 'Status', 'Check In', 'Check Out',
            'Late Minutes', 'Early Leave Minutes', 'Regular Worked Minutes', 'Leave Type',
            'Holiday', 'Override', 'Overtime Requested Minutes', 'Overtime Approved Minutes',
            'Overtime Start', 'Overtime Finish', 'Overtime Actual Minutes',
            'Overtime Credited Minutes', 'Overtime Session Status', 'Corrected Attendance', 'Needs Review',
        ], function () use ($recapData) {
            foreach ($recapData['recaps'] as $recap) {
                foreach ($recap['daily'] as $day) {
                    yield [
                        $recap['summary']['employee_code'], $recap['summary']['employee_name'],
                        $recap['summary']['job_title'] ?? '-', $recap['summary']['period'],
                        $day['date_string'], $day['day_name'], $day['effective_schedule_label'],
                        $day['schedule_source'], $day['shift']?->name ?? '-', $day['status_label'],
                        $day['check_in_at']?->format('Y-m-d H:i:s') ?? '-',
                        $day['check_out_at']?->format('Y-m-d H:i:s') ?? '-',
                        $day['late_minutes'], $day['early_leave_minutes'], $day['regular_worked_minutes'],
                        $day['leave_label'] ?? '-', $day['holiday_name'] ?? '-', $day['has_override'] ? 'Yes' : 'No',
                        $day['overtime_requested_minutes'], $day['overtime_approved_minutes'],
                        $day['overtime_start_at']?->format('Y-m-d H:i:s') ?? '-',
                        $day['overtime_finish_at']?->format('Y-m-d H:i:s') ?? '-',
                        $day['overtime_actual_minutes'], $day['overtime_credited_minutes'],
                        $day['overtime_session_status'] ?? '-', $day['is_corrected'] ? 'Yes' : 'No',
                        $day['needs_review'] ? 'Yes' : 'No',
                    ];
                }
            }
        });
    }

    protected function stream(string $filename, array $headers, callable $rows): StreamedResponse
    {
        return response()->stream(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($rows() as $row) {
                fputcsv($output, array_map([$this, 'sanitizeCell'], $row));
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    protected function sanitizeCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'{$value}" : $value;
    }
}
