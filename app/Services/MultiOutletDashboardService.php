<?php

namespace App\Services;

class MultiOutletDashboardService
{
    /**
     * Generate global overview metrics grouped by outlet.
     *
     * @param \Illuminate\Database\Eloquent\Collection<\App\Models\Outlet> $activeOutlets
     * @param array $attendanceItems (from AttendanceMonitoringService->getAttendanceMonitoringList)
     * @param array $exceptions (from OperationalExceptionService->generate)
     * @return array
     */
    public function generateOverview(\Illuminate\Database\Eloquent\Collection $activeOutlets, array $attendanceItems, array $exceptions): array
    {
        $outletsSummary = [];
        $globalKpi = [
            'total_outlets' => $activeOutlets->count(),
            'total_employees' => 0,
            'present_today' => 0,
            'late_today' => 0,
            'pending_today' => 0,
            'leave_today' => 0,
            'overtime_active' => $exceptions['summary']['active_overtime'] ?? 0,
            'total_exceptions' => $exceptions['summary']['total'] ?? 0,
        ];

        // Group Attendance Items by Outlet
        $attendanceByOutlet = collect($attendanceItems)->groupBy(fn ($item) => $item['employee']->outlet_id);

        // Group Exceptions by Outlet
        $exceptionsByOutlet = [];
        foreach ($exceptions['items'] ?? [] as $exItem) {
            $outletId = $exItem['employee']?->outlet_id;
            // Handle exceptions without employee (e.g. backup health) by assigning to "global" or skip
            if ($outletId) {
                if (!isset($exceptionsByOutlet[$outletId])) {
                    $exceptionsByOutlet[$outletId] = [];
                }
                $exceptionsByOutlet[$outletId][] = $exItem;
            }
        }

        foreach ($activeOutlets as $outlet) {
            $outletItems = $attendanceByOutlet->get($outlet->id, collect());
            $outletExceptions = $exceptionsByOutlet[$outlet->id] ?? [];

            $totalEmployees = $outletItems->count();
            $present = $outletItems->filter(fn ($i) => in_array($i['status_key'], ['present', 'late'], true))->count();
            $late = $outletItems->filter(fn ($i) => $i['status_key'] === 'late')->count();
            $pending = $outletItems->filter(fn ($i) => $i['status_key'] === 'pending')->count();
            $leave = $outletItems->filter(fn ($i) => in_array($i['status_key'], ['permission', 'sick', 'leave'], true))->count();

            // Needs attention logic
            $attentionItems = array_filter($outletExceptions, fn ($i) => in_array($i['severity'], ['warning', 'critical'], true));
            $needsAttention = count($attentionItems) > 0;
            $criticalCount = count(array_filter($attentionItems, fn ($i) => $i['severity'] === 'critical'));

            $outletsSummary[] = [
                'outlet' => $outlet,
                'metrics' => [
                    'total_employees' => $totalEmployees,
                    'present' => $present,
                    'late' => $late,
                    'pending' => $pending,
                    'leave' => $leave,
                    'present_rate' => $totalEmployees > 0 ? round(($present / $totalEmployees) * 100) : 0,
                ],
                'exceptions_count' => count($outletExceptions),
                'needs_attention' => $needsAttention,
                'critical_count' => $criticalCount,
            ];

            // Accumulate Global KPI
            $globalKpi['total_employees'] += $totalEmployees;
            $globalKpi['present_today'] += $present;
            $globalKpi['late_today'] += $late;
            $globalKpi['pending_today'] += $pending;
            $globalKpi['leave_today'] += $leave;
        }

        // Sort outlets: those with critical issues first, then needs attention, then by name
        usort($outletsSummary, function ($a, $b) {
            if ($a['critical_count'] !== $b['critical_count']) {
                return $b['critical_count'] <=> $a['critical_count'];
            }
            if ($a['needs_attention'] !== $b['needs_attention']) {
                return $b['needs_attention'] <=> $a['needs_attention'];
            }
            return strcmp($a['outlet']->name, $b['outlet']->name);
        });

        return [
            'global_kpi' => $globalKpi,
            'outlets' => $outletsSummary,
        ];
    }
}
