<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\DocumentoRequest;
use App\Http\Requests\DocVersion\DocVersionRequest;
use App\Http\Resources\DocCategoryResource;
use App\Http\Resources\DocumentoResource;
use App\Http\Resources\DocVersionResource;
use App\Models\DocCategory;
use App\Models\Documento;
use App\Models\DocVersion;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    use PaginatesRequests;

    public function categories(): AnonymousResourceCollection
    {
        return DocCategoryResource::collection(DocCategory::query()->orderBy('sort_order')->get());
    }

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Documento::query()->orderByDesc('updated_at');

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

    public function show(Documento $documento): DocumentoResource
    {
        return new DocumentoResource($documento);
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
