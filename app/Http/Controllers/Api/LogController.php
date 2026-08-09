<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Models\AuditLog;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;

/**
 * Módulo Logs de Actividade — só leitura sobre o trilho de auditoria
 * transversal (`App\Concerns\LogsAuditTrail`, ver SIG-IIV-MEMORIA-PROJETO.md
 * secção 15). Sem create/update/delete: os registos nascem automaticamente
 * dos boot hooks dos modelos auditados.
 */
class LogController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = AuditLog::query()->with('actor')->orderByDesc('created_at');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }

        if ($entityType = $request->string('entity_type')->toString()) {
            $query->where('auditable_type', 'like', "%{$entityType}");
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->string('date_to')->toString()) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return PaginatedResponse::make($query->paginate($perPage), LogResource::class);
    }
}
