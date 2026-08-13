<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filter = $request->input('filter', 'all');

        $query = $filter === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Server-side Authorization: User can ONLY read their own notification
        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Notifikasi tidak ditemukan atau akses ditolak.'], 404);
            }
            abort(403, 'Akses ditolak. Notifikasi ini bukan milik Anda.');
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        $targetUrl = $notification->data['target_url'] ?? null;
        if ($targetUrl) {
            return redirect($targetUrl);
        }

        return redirect()->back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Server-side Authorization: Only affects authenticated user's unread notifications
        $user->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
