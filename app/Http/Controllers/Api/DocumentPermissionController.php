<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentPermission\DocumentPermissionRequest;
use App\Http\Resources\DocumentPermissionResource;
use App\Models\DocumentPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DocumentPermissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = DocumentPermission::query()->with('department');

        if ($documentId = $request->string('document_id')->toString()) {
            $query->where('document_id', $documentId);
        }

        return DocumentPermissionResource::collection($query->get());
    }

    public function store(DocumentPermissionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $permission = DocumentPermission::query()->create([
            'id' => (string) Str::uuid(),
            'document_id' => $data['documentId'],
            'role' => $data['role'] ?? null,
            'department_id' => $data['departmentId'] ?? null,
            'can_edit' => $data['canEdit'],
        ]);

        return (new DocumentPermissionResource($permission->load('department')))->response()->setStatusCode(201);
    }

    public function destroy(DocumentPermission $documentPermission): Response
    {
        $documentPermission->delete();

        return response()->noContent();
    }
}
