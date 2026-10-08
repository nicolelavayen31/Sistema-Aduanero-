<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(50)
            ->get();

        return ApiResponse::success([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ], 'Notificaciones del usuario');
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return ApiResponse::error('Notificación no encontrada', [], 404);
        }

        $notification->markAsRead();

        return ApiResponse::success(null, 'Notificación marcada como leída');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(null, 'Todas las notificaciones fueron marcadas como leídas');
    }
}
