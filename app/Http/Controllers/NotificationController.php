<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Cloche de notifications du site (canal database). */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()
                ->latest()
                ->limit((int) $request->query('limit', 20))
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'url' => $n->data['url'] ?? null,
                    'icon' => $n->data['icon'] ?? 'bell',
                    'type' => $n->data['type'] ?? null,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ]),
        ]);
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }
}
