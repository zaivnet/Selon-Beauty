<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Services\OvertimeSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OvertimeSessionController extends Controller
{
    public function __construct(protected OvertimeSessionService $sessionService) {}

    public function start(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        try {
            $this->sessionService->start($request->user(), $overtimeRequest->id, $request->all());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Sesi lembur berhasil dimulai.');
    }

    public function finish(Request $request, OvertimeSession $overtimeSession): RedirectResponse
    {
        try {
            $this->sessionService->finish($request->user(), $overtimeSession->id, $request->all());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Sesi lembur berhasil diselesaikan.');
    }
}
