<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessType\ProcessTypeRequest;
use App\Http\Requests\ProcessTypeStep\ProcessTypeStepRequest;
use App\Http\Resources\ProcessTypeResource;
use App\Http\Resources\ProcessTypeStepResource;
use App\Models\ProcessType;
use App\Models\ProcessTypeStep;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProcessTypeController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = ProcessType::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return PaginatedResponse::make($query->paginate($perPage), ProcessTypeResource::class);
    }

    public function store(ProcessTypeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $type = ProcessType::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sla_days' => $data['slaDays'] ?? null,
            'is_active' => $data['isActive'] ?? true,
            'created_at' => now(),
        ]);

        return (new ProcessTypeResource($type))->response()->setStatusCode(201);
    }

    public function update(ProcessTypeRequest $request, ProcessType $processoTipo): ProcessTypeResource
    {
        $data = $request->validated();

        $processoTipo->update(array_filter([
            'name' => $data['name'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'icon' => array_key_exists('icon', $data) ? $data['icon'] : $processoTipo->icon,
            'sla_days' => array_key_exists('slaDays', $data) ? $data['slaDays'] : $processoTipo->sla_days,
            'is_active' => $data['isActive'] ?? null,
        ], fn ($v) => $v !== null));

        return new ProcessTypeResource($processoTipo);
    }

    public function destroy(ProcessType $processoTipo): Response
    {
        $processoTipo->delete();

        return response()->noContent();
    }

    public function steps(ProcessType $processoTipo): AnonymousResourceCollection
    {
        return ProcessTypeStepResource::collection($processoTipo->steps);
    }

    public function storeStep(ProcessTypeStepRequest $request, ProcessType $processoTipo): JsonResponse
    {
        $data = $request->validated();

        $step = ProcessTypeStep::query()->create([
            'id' => (string) Str::uuid(),
            'process_type_id' => $processoTipo->id,
            'order_index' => $data['orderIndex'],
            'name' => $data['name'],
            'default_role' => $data['defaultRole'] ?? null,
            'sla_days' => $data['slaDays'] ?? null,
        ]);

        return (new ProcessTypeStepResource($step))->response()->setStatusCode(201);
    }

    public function updateStep(ProcessTypeStepRequest $request, ProcessType $processoTipo, ProcessTypeStep $etapa): ProcessTypeStepResource
    {
        $data = $request->validated();

        $etapa->update([
            'order_index' => $data['orderIndex'],
            'name' => $data['name'],
            'default_role' => $data['defaultRole'] ?? null,
            'sla_days' => $data['slaDays'] ?? null,
        ]);

        return new ProcessTypeStepResource($etapa);
    }

    public function destroyStep(ProcessType $processoTipo, ProcessTypeStep $etapa): Response
    {
        $etapa->delete();

        return response()->noContent();
    }

    /** Reordena enviando a lista completa de ids na nova ordem (1-based). */
    public function reorderSteps(Request $request, ProcessType $processoTipo): AnonymousResourceCollection
    {
        $orderedIds = $request->validate(['orderedIds' => ['required', 'array']])['orderedIds'];

        foreach ($orderedIds as $index => $stepId) {
            ProcessTypeStep::query()
                ->where('id', $stepId)
                ->where('process_type_id', $processoTipo->id)
                ->update(['order_index' => $index + 1]);
        }

        return ProcessTypeStepResource::collection($processoTipo->fresh()->steps);
    }
}
