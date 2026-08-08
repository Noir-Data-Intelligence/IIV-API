<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessAttachment\ProcessAttachmentRequest;
use App\Http\Requests\ProcessEvent\ProcessEventRequest;
use App\Http\Requests\Processo\CreateProcessoRequest;
use App\Http\Requests\Processo\ProcessTransitionRequest;
use App\Http\Requests\Processo\UpdateProcessoRequest;
use App\Http\Requests\ProcessStep\UpdateProcessStepRequest;
use App\Http\Resources\ProcessAttachmentResource;
use App\Http\Resources\ProcessEventResource;
use App\Http\Resources\ProcessoResource;
use App\Http\Resources\ProcessStepResource;
use App\Models\ProcessAttachment;
use App\Models\Processo;
use App\Models\ProcessStep;
use App\Services\ProcessWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProcessoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly ProcessWorkflowService $workflow) {}

    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'abertos' => Processo::query()->where('status', 'aberto')->count(),
            'emCurso' => Processo::query()->where('status', 'em_curso')->count(),
            'concluidos' => Processo::query()->where('status', 'concluido')->count(),
            'atrasados' => Processo::query()
                ->whereIn('status', ['aberto', 'em_curso'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->count(),
        ]);
    }

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Processo::query()->orderByDesc('opened_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($typeId = $request->string('type_id')->toString()) {
            $query->where('type_id', $typeId);
        }
        if ($requesterId = $request->string('requester_id')->toString()) {
            $query->where('requester_id', $requesterId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), ProcessoResource::class);
    }

    public function show(Processo $processo): ProcessoResource
    {
        return new ProcessoResource($processo);
    }

    public function store(CreateProcessoRequest $request): JsonResponse
    {
        $processo = $this->workflow->criar($request->validated());

        return (new ProcessoResource($processo))->response()->setStatusCode(201);
    }

    public function update(UpdateProcessoRequest $request, Processo $processo): ProcessoResource
    {
        $data = $request->validated();

        $processo->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'priority' => $data['priority'] ?? null,
            'due_date' => array_key_exists('dueDate', $data) ? $data['dueDate'] : $processo->due_date,
            'status' => $data['status'] ?? null,
        ], fn ($v) => $v !== null));

        return new ProcessoResource($processo);
    }

    public function destroy(Processo $processo): Response
    {
        $processo->delete();

        return response()->noContent();
    }

    public function steps(Processo $processo): AnonymousResourceCollection
    {
        return ProcessStepResource::collection($processo->steps);
    }

    public function updateStep(UpdateProcessStepRequest $request, Processo $processo, ProcessStep $etapa): ProcessStepResource
    {
        $data = $request->validated();

        $etapa->update(array_filter([
            'status' => $data['status'] ?? null,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $etapa->notes,
            'started_at' => array_key_exists('startedAt', $data) ? $data['startedAt'] : $etapa->started_at,
            'completed_at' => array_key_exists('completedAt', $data) ? $data['completedAt'] : $etapa->completed_at,
        ], fn ($v) => $v !== null));

        return new ProcessStepResource($etapa);
    }

    public function events(Processo $processo): AnonymousResourceCollection
    {
        return ProcessEventResource::collection($processo->events);
    }

    public function storeEvent(ProcessEventRequest $request, Processo $processo): JsonResponse
    {
        $data = $request->validated();

        $event = $this->workflow->registarEvento(
            $processo,
            $data['actorId'] ?? null,
            $data['eventType'],
            $data['payload'] ?? null,
        );

        return (new ProcessEventResource($event))->response()->setStatusCode(201);
    }

    public function advance(ProcessTransitionRequest $request, Processo $processo): ProcessoResource
    {
        $data = $request->validated();
        $processo = $this->workflow->avancar($processo, $data['actorId'] ?? null, $data['notes'] ?? null);

        return new ProcessoResource($processo);
    }

    public function returnStep(ProcessTransitionRequest $request, Processo $processo): ProcessoResource
    {
        $data = $request->validated();
        $processo = $this->workflow->devolver($processo, $data['actorId'] ?? null, $data['notes'] ?? null);

        return new ProcessoResource($processo);
    }

    public function cancel(ProcessTransitionRequest $request, Processo $processo): ProcessoResource
    {
        $data = $request->validated();
        $processo = $this->workflow->cancelar($processo, $data['actorId'] ?? null, $data['notes'] ?? null);

        return new ProcessoResource($processo);
    }

    public function attachments(Processo $processo): AnonymousResourceCollection
    {
        return ProcessAttachmentResource::collection($processo->attachments()->with('document')->orderByDesc('created_at')->get());
    }

    public function storeAttachment(ProcessAttachmentRequest $request, Processo $processo): JsonResponse
    {
        $data = $request->validated();
        $documentId = $data['documentId'] ?? null;

        $attachment = ProcessAttachment::query()->create([
            'id' => (string) Str::uuid(),
            'process_id' => $processo->id,
            'label' => $data['label'],
            'file_path' => $documentId ? null : ($data['filePath'] ?? null),
            'document_id' => $documentId,
            'uploaded_by' => $data['uploadedBy'] ?? null,
            'created_at' => now(),
        ]);

        return (new ProcessAttachmentResource($attachment->load('document')))->response()->setStatusCode(201);
    }

    public function destroyAttachment(Processo $processo, ProcessAttachment $anexo): Response
    {
        $anexo->delete();

        return response()->noContent();
    }
}
