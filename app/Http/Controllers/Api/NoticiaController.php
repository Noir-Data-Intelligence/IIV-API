<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Noticia\NoticiaRequest;
use App\Http\Resources\NoticiaResource;
use App\Models\Noticia;
use App\Services\PermissionMatrixService;
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
 *
 * CORRECÇÃO (auditoria de segurança, 2026-08-09): "só honrado se o cliente
 * o enviar, nunca imposto" permitia a qualquer visitante ANÓNIMO omitir o
 * parâmetro e ler rascunhos não publicados (`GET /noticias?per_page=2000`).
 * O servidor `$request->user()` resolve-se a partir da cookie de sessão
 * mesmo sem `auth:sanctum` na rota (o guard `web` já autenticou no login) —
 * só um utilizador com o módulo `noticias` consegue ver não-publicados;
 * um anónimo ou sem essa permissão recebe sempre só os publicados,
 * independentemente do que enviar em `published`.
 */
class NoticiaController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly PermissionMatrixService $permissions) {}

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

        $canSeeUnpublished = ($user = $request->user())
            && collect($user->roleValues())->contains(fn ($role) => $this->permissions->canView($role, 'noticias'));

        if (! $canSeeUnpublished) {
            $query->where('published', true);
        } elseif ($request->has('published')) {
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
