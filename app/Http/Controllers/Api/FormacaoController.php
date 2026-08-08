<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Formacao\FormacaoRequest;
use App\Http\Resources\FormacaoResource;
use App\Models\Formacao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class FormacaoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Formacao::query()->orderByDesc('start_date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('trainer', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), FormacaoResource::class);
    }

    public function store(FormacaoRequest $request): JsonResponse
    {
        $formacao = Formacao::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new FormacaoResource($formacao))->response()->setStatusCode(201);
    }

    public function update(FormacaoRequest $request, Formacao $formacao): FormacaoResource
    {
        $formacao->update($this->toColumns($request->validated()));

        return new FormacaoResource($formacao);
    }

    public function destroy(Formacao $formacao): Response
    {
        $formacao->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'trainer' => $validated['trainer'] ?? null,
            'location' => $validated['location'] ?? null,
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'] ?? $validated['startDate'],
            'hours' => $validated['hours'] ?? 0,
            'status' => $validated['status'] ?? 'planeada',
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
