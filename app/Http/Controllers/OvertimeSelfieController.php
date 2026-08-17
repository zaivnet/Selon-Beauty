<?php

namespace App\Http\Controllers;

use App\Models\OvertimeSession;
use App\Services\OutletScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OvertimeSelfieController extends Controller
{
    public function __construct(protected OutletScopeService $outletScopeService) {}

    public function show(Request $request, OvertimeSession $overtimeSession, string $type): BinaryFileResponse
    {
        abort_unless(in_array($type, ['check_in', 'check_out'], true), 404);
        $user = $request->user();

        if ($user->role === 'admin') {
            $this->outletScopeService->ensureCanManageOvertimeSession($user, $overtimeSession);
        } elseif (! $this->outletScopeService->isGlobalScope($user)) {
            $authorized = ($user->role === 'employee' && $user->employee_id === $overtimeSession->employee_id);
            abort_unless($authorized, 403);
        }

        $path = $type === 'check_in' ? $overtimeSession->check_in_selfie_path : $overtimeSession->check_out_selfie_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
