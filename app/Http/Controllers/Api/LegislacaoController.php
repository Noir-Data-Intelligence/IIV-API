<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Legislacao\LegislacaoRequest;
use App\Http\Resources\LegislacaoResource;
use App\Models\Legislacao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * GET /legislacao serve DOIS formatos a partir do MESMO endpoint (ver
 * legislacao.ts no frontend): sem `page`/`per_page` -> array simples (site
 * publico); com `page`/`per_page` -> envelope Paginated<T> (admin via
 * <DataTable>). Publico (sem auth). POST/PUT/DELETE exigem auth+escrita.
 */
class LegislacaoController extends Controller
{
    public function index(Request $request): JsonResponse|array
    {
        $query = Legislacao::query()->orderBy('num');

        if ($tipo = $request->string('tipo')->toString()) {
            $query->where('tipo', $tipo);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%");
            });
        }
        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        if (! $request->has('page') && ! $request->has('per_page')) {
            return response()->json(LegislacaoResource::collection($query->get())->resolve());
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 2000));

        return PaginatedResponse::make($query->paginate($perPage), LegislacaoResource::class);
    }

    public function show(string $id): LegislacaoResource
    {
        $item = Legislacao::query()->where('id', $id)->orWhere('slug', $id)->firstOrFail();

        return new LegislacaoResource($item);
    }

    public function store(LegislacaoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $item = Legislacao::query()->create([
            'id' => (string) Str::uuid(),
            'num' => $data['num'],
            'slug' => $data['slug'] ?? Str::slug($data['titulo']),
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'ano' => $data['ano'],
            'pdf_url' => $data['pdfUrl'] ?? null,
            'published' => $data['published'] ?? false,
        ]);

        return (new LegislacaoResource($item))->response()->setStatusCode(201);
    }

    public function update(LegislacaoRequest $request, string $id): LegislacaoResource
    {
        $item = Legislacao::query()->where('id', $id)->orWhere('slug', $id)->firstOrFail();
        $data = $request->validated();

        $item->update([
            'num' => $data['num'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'ano' => $data['ano'],
            'pdf_url' => $data['pdfUrl'] ?? null,
            'published' => $data['published'] ?? false,
        ]);

        return new LegislacaoResource($item);
    }

    public function destroy(string $id): Response
    {
        $item = Legislacao::query()->where('id', $id)->orWhere('slug', $id)->firstOrFail();
        $item->delete();

        return response()->noContent();
    }
}
