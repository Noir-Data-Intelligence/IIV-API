<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sem paginacao server-side (ver notifications.ts) — devolve sempre a lista
 * completa DO UTILIZADOR AUTENTICADO, mais recente primeiro; a pagina faz
 * paginacao client-side. Todas as rotas sao scoped a `auth()->id()` — nunca
 * expor notificacoes de outro utilizador.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Notification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(NotificationResource::collection($rows)->resolve());
    }

    public function update(Request $request, Notification $notification): NotificationResource
    {
        abort_if($notification->user_id !== $request->user()->id, 404);

        $data = $request->validate([
            'read' => ['sometimes', 'boolean'],
        ]);

        $notification->update($data);

        return new NotificationResource($notification);
    }

    public function markAllRead(Request $request): Response
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->update(['read' => true]);

        return response()->noContent();
    }

    public function destroy(Request $request, Notification $notification): Response
    {
        abort_if($notification->user_id !== $request->user()->id, 404);
        $notification->delete();

        return response()->noContent();
    }

    public function clear(Request $request): Response
    {
        Notification::query()->where('user_id', $request->user()->id)->delete();

        return response()->noContent();
    }
}
