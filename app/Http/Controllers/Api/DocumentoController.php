<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocCategory\DocCategoryRequest;
use App\Http\Requests\Documento\DocumentoRequest;
use App\Http\Requests\DocVersion\DocVersionRequest;
use App\Http\Resources\DocCategoryResource;
use App\Http\Resources\DocumentoResource;
use App\Http\Resources\DocVersionResource;
use App\Models\DocCategory;
use App\Models\Documento;
use App\Models\DocVersion;
use App\Models\User;
use App\Support\PaginatedResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentoController extends Controller
{
    use PaginatesRequests;

    public function categories(): AnonymousResourceCollection
    {
        return DocCategoryResource::collection(DocCategory::query()->orderBy('sort_order')->get());
    }

    public function categoriesStore(DocCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = DocCategory::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sortOrder'] ?? 0,
        ]);

        return (new DocCategoryResource($category))->response()->setStatusCode(201);
    }

    public function categoriesUpdate(DocCategoryRequest $request, DocCategory $docCategory): DocCategoryResource
    {
        $data = $request->validated();

        $docCategory->update(array_filter([
            'name' => $data['name'] ?? null,
            'icon' => array_key_exists('icon', $data) ? $data['icon'] : $docCategory->icon,
            'sort_order' => array_key_exists('sortOrder', $data) ? $data['sortOrder'] : $docCategory->sort_order,
        ], fn ($v) => $v !== null));

        return new DocCategoryResource($docCategory);
    }

    public function categoriesDestroy(DocCategory $docCategory): Response
    {
        if (Documento::query()->where('category_id', $docCategory->id)->exists()) {
            abort(422, 'Não é possível eliminar uma categoria associada a documentos existentes.');
        }

        $docCategory->delete();

        return response()->noContent();
    }

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Documento::query()->orderByDesc('updated_at');
        $this->scopeToVisible($query, $request->user());

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($categoryId = $request->string('category_id')->toString()) {
            $query->where('category_id', $categoryId);
        }

        return PaginatedResponse::make($query->paginate($perPage), DocumentoResource::class);
    }

    public function show(Documento $documento, Request $request): DocumentoResource
    {
        // 404 (não 403): um documento `privado` fora do alcance do
        // utilizador não deve sequer confirmar que existe (auditoria de
        // segurança, 2026-08-09 — C3).
        if (! $this->canView($documento, $request->user())) {
            throw new NotFoundHttpException();
        }

        return new DocumentoResource($documento);
    }

    /**
     * Restringe a listagem aos documentos que o utilizador pode ver:
     * `publico` sempre visível; dono sempre vê o seu; `privado`/`departamento`
     * só visíveis com uma concessão explícita em `document_permissions`
     * (por papel ou por departamento do utilizador). Admin vê tudo.
     * Mesma regra aplicada em `canView()` para o `show()` — mantidas
     * sincronizadas de propósito (ver C3 na auditoria de segurança).
     */
    private function scopeToVisible(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $roles = array_map(fn ($r) => $r->value, $user->roleValues());
        $departmentIds = $user->departments()->pluck('departments.id')->all();

        $query->where(function (Builder $q) use ($user, $roles, $departmentIds) {
            $q->where('visibility', 'publico')
                ->orWhere('owner_id', $user->id);

            if ($roles !== [] || $departmentIds !== []) {
                $q->orWhereHas('permissions', fn (Builder $p) => $this->grantedTo($p, $roles, $departmentIds));
            }
        });
    }

    private function canView(Documento $documento, User $user): bool
    {
        if ($user->isAdmin() || $documento->visibility === 'publico' || $documento->owner_id === $user->id) {
            return true;
        }

        $roles = array_map(fn ($r) => $r->value, $user->roleValues());
        $departmentIds = $user->departments()->pluck('departments.id')->all();

        if ($roles === [] && $departmentIds === []) {
            return false;
        }

        return $documento->permissions()
            ->where(fn (Builder $p) => $this->grantedTo($p, $roles, $departmentIds))
            ->exists();
    }

    /** @param list<string> $roles @param list<string> $departmentIds */
    private function grantedTo(Builder $query, array $roles, array $departmentIds): void
    {
        $query->when($roles !== [], fn (Builder $q) => $q->whereIn('role', $roles))
            ->when($departmentIds !== [], fn (Builder $q) => $q->orWhereIn('department_id', $departmentIds));
    }

    public function store(DocumentoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $documento = Documento::query()->create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['categoryId'] ?? null,
            'owner_id' => $request->user()->id,
            'status' => $data['status'] ?? 'rascunho',
            'visibility' => $data['visibility'] ?? 'publico',
            'expiry_date' => $data['expiryDate'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);

        return (new DocumentoResource($documento))->response()->setStatusCode(201);
    }

    public function update(DocumentoRequest $request, Documento $documento): DocumentoResource
    {
        $data = $request->validated();

        $documento->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'category_id' => array_key_exists('categoryId', $data) ? $data['categoryId'] : $documento->category_id,
            'status' => $data['status'] ?? null,
            'visibility' => $data['visibility'] ?? null,
            'expiry_date' => array_key_exists('expiryDate', $data) ? $data['expiryDate'] : $documento->expiry_date,
            'tags' => $data['tags'] ?? null,
        ], fn ($v) => $v !== null));

        return new DocumentoResource($documento);
    }

    public function destroy(Documento $documento): Response
    {
        $documento->delete();

        return response()->noContent();
    }

    public function versions(Documento $documento): AnonymousResourceCollection
    {
        return DocVersionResource::collection(
            $documento->versions()->orderByDesc('version_number')->get()
        );
    }

    /** RN: uma nova versão passa a ser a corrente e devolve o documento a rascunho (ver Documentos.tsx/mock). */
    public function storeVersion(DocVersionRequest $request, Documento $documento): JsonResponse
    {
        $data = $request->validated();
        $nextNumber = ($documento->versions()->max('version_number') ?? 0) + 1;

        $version = DocVersion::query()->create([
            'id' => (string) Str::uuid(),
            'document_id' => $documento->id,
            'version_number' => $nextNumber,
            'file_path' => $data['filePath'] ?? "documents/{$documento->id}/v{$nextNumber}/ficheiro.bin",
            'file_name' => $data['fileName'] ?? null,
            'file_size' => $data['fileSize'] ?? null,
            'mime_type' => $data['mimeType'] ?? null,
            'change_notes' => $data['changeNotes'] ?? "Versão {$nextNumber}",
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $documento->update(['current_version_id' => $version->id, 'status' => 'rascunho']);

        return (new DocVersionResource($version))->response()->setStatusCode(201);
    }
}
