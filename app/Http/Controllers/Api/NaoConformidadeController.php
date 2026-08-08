<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\NaoConformidade\NaoConformidadeRequest;
use App\Http\Resources\NaoConformidadeResource;
use App\Models\NaoConformidade;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NaoConformidadeController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = NaoConformidade::query()->with(['department', 'auditoria'])->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($severity = $request->string('severity')->toString()) {
            $query->where('severity', $severity);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($departmentId = $request->string('department_id')->toString()) {
            $query->where('department_id', $departmentId);
        }
        if ($auditId = $request->string('audit_id')->toString()) {
            $query->where('audit_id', $auditId);
        }

        return PaginatedResponse::make($query->paginate($perPage), NaoConformidadeResource::class);
    }

    public function show(NaoConformidade $naoConformidade): NaoConformidadeResource
    {
        return new NaoConformidadeResource($naoConformidade->load(['department', 'auditoria']));
    }

    public function store(NaoConformidadeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $nc = NaoConformidade::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($data),
            'resolved_at' => $this->resolvedAtFor($data['status'] ?? 'aberta', null),
            'created_at' => now(),
        ]);

        return (new NaoConformidadeResource($nc->load(['department', 'auditoria'])))->response()->setStatusCode(201);
    }

    public function update(NaoConformidadeRequest $request, NaoConformidade $naoConformidade): NaoConformidadeResource
    {
        $data = $request->validated();
        $status = $data['status'] ?? $naoConformidade->status;

        $naoConformidade->update([
            ...$this->toColumns($data),
            'resolved_at' => $this->resolvedAtFor($status, $naoConformidade->resolved_at),
        ]);

        return new NaoConformidadeResource($naoConformidade->load(['department', 'auditoria']));
    }

    public function destroy(NaoConformidade $naoConformidade): Response
    {
        $naoConformidade->delete();

        return response()->noContent();
    }

    /** RN: grava `resolvedAt` só na 1ª vez que o estado passa a resolvida/encerrada; limpa se reabrir (mesmo padrão de DashboardAlertHistory). */
    private function resolvedAtFor(string $status, ?Carbon $current): ?Carbon
    {
        $isResolved = in_array($status, ['resolvida', 'encerrada'], true);
        if ($isResolved) {
            return $current ?? now();
        }

        return null;
    }

    private function toColumns(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => $validated['status'] ?? 'aberta',
            'corrective_action' => $validated['correctiveAction'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'department_id' => $validated['departmentId'] ?? null,
            'audit_id' => $validated['auditId'] ?? null,
        ];
    }
}
