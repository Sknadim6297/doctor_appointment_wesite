<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowNotificationController extends Controller
{
    public function __construct(
        private readonly WorkflowNotificationService $notifications,
    ) {
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->notifications->unreadCount(Auth::user()),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $items = $this->notifications->recentForUser($user, 30);

        return response()->json([
            'notifications' => $items->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'action_url' => $n->action_url,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->diffForHumans(),
                'actor' => $n->actor?->name,
                'enrollment_id' => $n->enrollment_id,
            ]),
            'unread_count' => $this->notifications->unreadCount($user),
        ]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $ok = $this->notifications->markRead($notification, Auth::user());

        return response()->json([
            'success' => $ok,
            'unread_count' => $this->notifications->unreadCount(Auth::user()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead(Auth::user());

        return response()->json([
            'success' => true,
            'marked' => $count,
            'unread_count' => 0,
        ]);
    }
}
