<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'],
                'message' => $notification->data['message'],
                'ticket_number' => $notification->data['ticket_number'],
                'time' => $notification->created_at->wib()->diffForHumans(),
                'read_url' => route('admin.notifications.read', $notification->id),
            ]);

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $ticket = Ticket::where('uuid', $item->data['ticket_uuid'])->firstOrFail();

        return response()->json(['url' => route('admin.tickets.show', $ticket)]);
    }
}
