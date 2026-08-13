<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveAttachmentController extends Controller
{
    public function show(Request $request, LeaveRequest $leaveRequest): StreamedResponse
    {
        $user = Auth::user();

        // Authorization check: Owner, Admin, or the owning Employee
        $isAuthorized = in_array($user->role, ['superadmin', 'owner', 'admin'], true)
            || ($user->role === 'employee' && $user->employee_id === $leaveRequest->employee_id);

        if (! $isAuthorized) {
            abort(403, 'Akses ditolak. Anda tidak berhak melihat lampiran ini.');
        }

        $path = $leaveRequest->attachment_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        return Storage::disk('local')->response($path);
    }
}
