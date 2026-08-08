<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentLink\DocumentLinkRequest;
use App\Http\Resources\DocumentLinkResource;
use App\Models\DocumentLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DocumentLinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = DocumentLink::query()->with('document')->orderByDesc('created_at');

        if ($entityType = $request->string('entity_type')->toString()) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->string('entity_id')->toString()) {
            $query->where('entity_id', $entityId);
        }

        return DocumentLinkResource::collection($query->get());
    }

    public function store(DocumentLinkRequest $request): JsonResponse
    {
        $data = $request->validated();

        $link = DocumentLink::query()->create([
            'id' => (string) Str::uuid(),
            'document_id' => $data['documentId'],
            'entity_type' => $data['entityType'],
            'entity_id' => $data['entityId'],
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        return (new DocumentLinkResource($link->load('document')))->response()->setStatusCode(201);
    }

    public function destroy(DocumentLink $documentLink): Response
    {
        $documentLink->delete();

        return response()->noContent();
    }
}
