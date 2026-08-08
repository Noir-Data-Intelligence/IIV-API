<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlertHistoryResource;
use App\Models\DashboardAlertHistory;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Recurso partilhado entre Dashboard.tsx (POST, quando uma metrica cruza o
 * limiar, com debounce de 1h por metrica) e HistoricoAlertas.tsx (CRUD de
 * gestao). Filtros de query usam snake_case (metric_key/action_status),
 * ver dashboardAlertHistory.ts.
 */
class DashboardAlertHistoryController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = DashboardAlertHistory::query()
            ->with(['assignee', 'department'])
            ->orderByDesc('created_at');

        if ($metricKey = $request->string('metric_key')->toString()) {
            $query->where('metric_key', $metricKey);
        }
        if ($actionStatus = $request->string('action_status')->toString()) {
            $query->where('action_status', $actionStatus);
        }
        if ($from = $request->string('from')->toString()) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->string('to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }

        return PaginatedResponse::make($query->paginate($perPage), AlertHistoryResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'metricKey' => ['required', 'string'],
            'label' => ['required', 'string'],
            'value' => ['required', 'numeric'],
            'threshold' => ['required', 'numeric'],
            'tone' => ['required', 'string'],
        ]);

        $userId = $request->user()->id;

        // Debounce: ignora silenciosamente se ja existe registo da mesma
        // metrica na ultima hora para este utilizador (replica a versao
        // Supabase original).
        $recent = DashboardAlertHistory::query()
            ->where('user_id', $userId)
            ->where('metric_key', $data['metricKey'])
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($recent) {
            return response()->json(null, 200);
        }

        $row = DashboardAlertHistory::query()->create([
            'id' => (string) Str::uuid(),
            'metric_key' => $data['metricKey'],
            'label' => $data['label'],
            'value' => $data['value'],
            'threshold' => $data['threshold'],
            'tone' => $data['tone'],
            'user_id' => $userId,
            'action_status' => 'pendente',
            'created_at' => now(),
        ]);

        return (new AlertHistoryResource($row))->response()->setStatusCode(201);
    }

    public function update(Request $request, DashboardAlertHistory $dashboardAlertHistory): AlertHistoryResource
    {
        $data = $request->validate([
            'assignedTo' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'assignedDepartmentId' => ['sometimes', 'nullable', 'uuid', 'exists:departments,id'],
            'actionStatus' => ['sometimes', 'string', 'in:pendente,em_curso,resolvido'],
            'actionNotes' => ['sometimes', 'nullable', 'string'],
        ]);

        $assignedTo = array_key_exists('assignedTo', $data) ? $data['assignedTo'] : $dashboardAlertHistory->assigned_to;
        $actionStatus = $data['actionStatus'] ?? $dashboardAlertHistory->action_status;

        // Regra: primeira atribuicao grava assigned_at.
        $assignedAt = $dashboardAlertHistory->assigned_at;
        if ($assignedTo && ! $dashboardAlertHistory->assigned_at) {
            $assignedAt = now();
        }

        // Regra: ao resolver grava resolved_at; ao reabrir limpa-o.
        $resolvedAt = $dashboardAlertHistory->resolved_at;
        if ($actionStatus === 'resolvido' && ! $dashboardAlertHistory->resolved_at) {
            $resolvedAt = now();
        } elseif ($actionStatus !== 'resolvido') {
            $resolvedAt = null;
        }

        $dashboardAlertHistory->update([
            'assigned_to' => $assignedTo,
            'assigned_department_id' => array_key_exists('assignedDepartmentId', $data) ? $data['assignedDepartmentId'] : $dashboardAlertHistory->assigned_department_id,
            'action_status' => $actionStatus,
            'action_notes' => array_key_exists('actionNotes', $data) ? $data['actionNotes'] : $dashboardAlertHistory->action_notes,
            'assigned_at' => $assignedAt,
            'resolved_at' => $resolvedAt,
        ]);

        return new AlertHistoryResource($dashboardAlertHistory->load(['assignee', 'department']));
    }

    public function clear(Request $request): Response
    {
        DashboardAlertHistory::query()->where('user_id', $request->user()->id)->delete();

        return response()->noContent();
    }
}
