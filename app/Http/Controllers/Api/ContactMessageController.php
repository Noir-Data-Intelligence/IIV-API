<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessage\CreateContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * POST e publico (formulario de Contactos, rate-limited — ver routes/api.php).
 * GET/PATCH/DELETE servem a caixa de entrada admin (Mensagens.tsx), exigem
 * auth + permissao do modulo "mensagens".
 */
class ContactMessageController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = ContactMessage::query()->orderByDesc('created_at');

        return PaginatedResponse::make($query->paginate($perPage), ContactMessageResource::class);
    }

    public function store(CreateContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::query()->create([
            'id' => (string) Str::uuid(),
            ...$request->validated(),
            'created_at' => now(),
        ]);

        return response()->json(['id' => $message->id], 201);
    }

    public function update(Request $request, ContactMessage $contactMessage): ContactMessageResource
    {
        $data = $request->validate([
            'lida' => ['sometimes', 'boolean'],
            'respondida' => ['sometimes', 'boolean'],
        ]);

        $contactMessage->update($data);

        return new ContactMessageResource($contactMessage);
    }

    public function destroy(ContactMessage $contactMessage): Response
    {
        $contactMessage->delete();

        return response()->noContent();
    }
}
