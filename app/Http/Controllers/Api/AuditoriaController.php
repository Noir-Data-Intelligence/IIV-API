<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auditoria\AuditoriaRequest;
use App\Http\Resources\AuditoriaResource;
use App\Models\Auditoria;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AuditoriaController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Auditoria::query()->with(['department', 'laboratorio'])->orderByDesc('scheduled_date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('auditor', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), AuditoriaResource::class);
    }

    public function show(Auditoria $auditoria): AuditoriaResource
    {
        return new AuditoriaResource($auditoria->load(['department', 'laboratorio']));
    }

    public function store(AuditoriaRequest $request): JsonResponse
    {
        $auditoria = Auditoria::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new AuditoriaResource($auditoria->load(['department', 'laboratorio'])))->response()->setStatusCode(201);
    }

    public function update(AuditoriaRequest $request, Auditoria $auditoria): AuditoriaResource
    {
        $auditoria->update($this->toColumns($request->validated()));

        return new AuditoriaResource($auditoria->load(['department', 'laboratorio']));
    }

    public function destroy(Auditoria $auditoria): Response
    {
        $auditoria->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'audit_type' => $validated['auditType'],
            'auditor' => $validated['auditor'],
            'scheduled_date' => $validated['scheduledDate'],
            'completed_date' => $validated['completedDate'] ?? null,
            'status' => $validated['status'] ?? 'planeada',
            'findings' => $validated['findings'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'department_id' => $validated['departmentId'] ?? null,
            'laboratory_id' => $validated['laboratoryId'] ?? null,
        ];
    }
}
