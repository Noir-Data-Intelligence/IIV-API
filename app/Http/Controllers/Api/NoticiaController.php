<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Noticia\NoticiaRequest;
use App\Http\Resources\NoticiaResource;
use App\Models\Noticia;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * GET é publico (sem auth:sanctum) — usado tanto pelo site publico
 * (Noticias.tsx, published=true explicito) como pelo admin (sem filtro,
 * ve tudo), exactamente como o handler mock faz (ver noticias.ts): o
 * filtro `published` e so honrado se o cliente o enviar, nunca imposto
 * pelo servidor. POST/PUT/DELETE exigem auth + permissao de escrita.
 */
class NoticiaController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Noticia::query()
            ->orderByDesc('destaque')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('resumo', 'like', "%{$search}%")
                    ->orWhere('conteudo', 'like', "%{$search}%");
            });
        }
        if ($categoria = $request->string('categoria')->toString()) {
            $query->where('categoria', $categoria);
        }
        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        return PaginatedResponse::make($query->paginate($perPage), NoticiaResource::class);
    }

    public function show(string $id): NoticiaResource
    {
        $noticia = Noticia::query()->where('id', $id)->orWhere('slug', $id)->firstOrFail();

        return new NoticiaResource($noticia);
    }

    public function store(NoticiaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $titulo = $data['titulo'];

        $noticia = Noticia::query()->create([
            'id' => (string) Str::uuid(),
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($titulo).'-'.Str::random(4),
            'published_at' => $data['published_at'] ?? (($data['published'] ?? false) ? now() : null),
        ]);

        return (new NoticiaResource($noticia))->response()->setStatusCode(201);
    }

    public function update(NoticiaRequest $request, Noticia $noticia): NoticiaResource
    {
        $noticia->update($request->validated());

        return new NoticiaResource($noticia);
    }

    public function destroy(Noticia $noticia): Response
    {
        $noticia->delete();

        return response()->noContent();
    }
}
