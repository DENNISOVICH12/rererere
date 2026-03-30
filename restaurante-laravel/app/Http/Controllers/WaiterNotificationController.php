<?php

namespace App\Http\Controllers;

use App\Models\WaiterNotification;
use App\Services\WaiterNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaiterNotificationController extends Controller
{
    public function index(Request $request, WaiterNotificationService $service): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        $notifications = WaiterNotification::query()
            ->where('restaurant_id', $restaurantId)
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $notifications->map(fn (WaiterNotification $notification) => $service->transform($notification))->values(),
            'meta' => [
                'unread_count' => $notifications->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function markRead(Request $request, WaiterNotification $notification, WaiterNotificationService $service): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);
        abort_unless((int) $notification->restaurant_id === $restaurantId, 403);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'data' => $service->transform($notification->fresh()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        WaiterNotification::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
