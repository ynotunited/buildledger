<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user (paginated).
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Return unread count + latest 5 unread (for the bell dropdown).
     */
    public function unread(Request $request)
    {
        $user  = $request->user();
        $count = $user->unreadNotifications()->count();
        $items = $user->unreadNotifications()->latest()->take(5)->get()->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type']    ?? null,
            'title'      => $n->data['title']   ?? 'Notification',
            'message'    => $n->data['message'] ?? '',
            'link'       => $n->data['link']    ?? null,
            'created_at' => $n->created_at,
        ]);

        return response()->json(['count' => $count, 'items' => $items]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All marked as read']);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
