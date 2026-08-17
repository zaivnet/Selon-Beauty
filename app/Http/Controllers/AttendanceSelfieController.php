<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\OutletScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceSelfieController extends Controller
{
    public function __construct(protected OutletScopeService $outletScopeService) {}

    /**
     * Serve private attendance selfie image to authorized users.
     */
    public function show(Request $request, int|string $recordId, string $type): BinaryFileResponse
    {
        if (! in_array($type, ['check_in', 'check_out'], true)) {
            abort(404, 'Tipe foto selfie tidak valid.');
        }

        $record = AttendanceRecord::findOrFail($recordId);
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Authorization check:
        // Superadmin/Owner: allowed to view any employee's selfie.
        // Admin: allowed ONLY IF employee belongs to Admin's outlet.
        // Employee: allowed ONLY IF record belongs to their employee_id.
        if ($user->role === 'admin') {
            $this->outletScopeService->ensureCanManageAttendance($user, $record);
        } elseif (! $this->outletScopeService->isGlobalScope($user)) {
            $isOwnRecord = $user->employee && $record->employee_id === $user->employee->id;
            if (! $isOwnRecord) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat foto selfie ini.');
            }
        }

        $selfiePath = ($type === 'check_in') ? $record->check_in_selfie_path : $record->check_out_selfie_path;

        if (! $selfiePath || ! Storage::disk('local')->exists($selfiePath)) {
            abort(404, 'File foto selfie tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($selfiePath);
        $mimeType = Storage::disk('local')->mimeType($selfiePath) ?: 'image/jpeg';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
