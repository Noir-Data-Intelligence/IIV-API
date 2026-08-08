<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertAckResource;
use App\Models\DashboardAlertAck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardAlertAckController extends Controller
{
    /** GET -> acks ainda validos (nao expirados) do utilizador autenticado. */
    public function index(Request $request): JsonResponse
    {
        $rows = DashboardAlertAck::query()
            ->where('user_id', $request->user()->id)
            ->where('acknowledged_until', '>', now())
            ->get();

        return response()->json(AlertAckResource::collection($rows)->resolve());
    }

    /** POST -> upsert (snooze por `hours` a partir de agora). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'metricKey' => ['required', 'string'],
            'hours' => ['required', 'numeric', 'min:0'],
        ]);

        // 'id' fora do payload de update — o Model (HasUuids) so o gera na
        // criacao; incluir aqui sobrescreveria a PK num upsert existente.
        $ack = DashboardAlertAck::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'metric_key' => $data['metricKey']],
            ['acknowledged_until' => now()->addHours((float) $data['hours'])],
        );

        return (new AlertAckResource($ack))->response()->setStatusCode(201);
    }

    /** DELETE /:metricKey -> reactivar (remover ack). */
    public function destroy(Request $request, string $metricKey): Response
    {
        DashboardAlertAck::query()
            ->where('user_id', $request->user()->id)
            ->where('metric_key', $metricKey)
            ->delete();

        return response()->noContent();
    }
}
