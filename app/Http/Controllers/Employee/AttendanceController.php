<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $attendanceService) {}

    public function checkIn(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $record = $this->attendanceService->checkIn($request->user(), $request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Absensi masuk berhasil dicatat.',
                    'data' => $record,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Absensi masuk berhasil dicatat pada '.$record->check_in_at->format('H:i').' WIB.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function checkOut(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $record = $this->attendanceService->checkOut($request->user(), $request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Absensi keluar berhasil dicatat.',
                    'data' => $record,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Absensi keluar berhasil dicatat pada '.$record->check_out_at->format('H:i').' WIB.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
