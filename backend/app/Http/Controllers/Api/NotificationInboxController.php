<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app notification inbox (M8.1). Reads the Laravel database-channel
 * notifications for the signed-in user; supports mark-one and mark-all read.
 */
class NotificationInboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['data' => []]);
        }

        $notifications = $user->notifications()->latest()->limit(50)->get();

        return response()->json([
            'data' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->data['title'] ?? null,
                'body' => $n->data['body'] ?? null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $data = $request->validate([
            'id' => ['nullable', 'string'],
        ]);

        if (! empty($data['id'])) {
            $notification = $user->notifications()->where('id', $data['id'])->first();

            if ($notification !== null) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}