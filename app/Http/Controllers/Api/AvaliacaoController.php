<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Avaliacao\CriteriaRequest;
use App\Http\Requests\Avaliacao\CycleRequest;
use App\Http\Requests\Avaliacao\EvaluationRejectRequest;
use App\Http\Requests\Avaliacao\EvaluationRequest;
use App\Http\Requests\Avaliacao\EvaluationTransitionRequest;
use App\Http\Resources\EvaluationCriteriaResource;
use App\Http\Resources\EvaluationCycleResource;
use App\Http\Resources\EvaluationHistoryResource;
use App\Http\Resources\EvaluationResource;
use App\Http\Resources\EvaluationScoreResource;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationCycle;
use App\Models\EvaluationHistory;
use App\Services\EvaluationWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AvaliacaoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly EvaluationWorkflowService $workflow) {}

    public function stats(): JsonResponse
    {
        $activeCycle = EvaluationCycle::query()->where('status', 'aberto')->orderByDesc('year')->first();

        return response()->json([
            'total' => Evaluation::query()->count(),
            'pending' => Evaluation::query()->where('status', 'submetida')->count(),
            'avgScore' => Evaluation::query()->whereNotNull('global_score')->avg('global_score'),
            'activeCycleName' => $activeCycle?->name,
        ]);
    }

    public function cyclesIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = EvaluationCycle::query()->orderByDesc('year');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), EvaluationCycleResource::class);
    }

    public function cyclesStore(CycleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cycle = EvaluationCycle::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'year' => $data['year'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'status' => $data['status'] ?? 'planeado',
            'description' => $data['description'] ?? null,
            'created_at' => now(),
        ]);

        return (new EvaluationCycleResource($cycle))->response()->setStatusCode(201);
    }

    public function cyclesUpdate(CycleRequest $request, EvaluationCycle $ciclo): EvaluationCycleResource
    {
        $data = $request->validated();
        $ciclo->update(array_filter([
            'name' => $data['name'] ?? null,
            'year' => $data['year'] ?? null,
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
            'status' => $data['status'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : $ciclo->description,
        ], fn ($v) => $v !== null));

        return new EvaluationCycleResource($ciclo);
    }

    public function cyclesDestroy(EvaluationCycle $ciclo): Response
    {
        $ciclo->delete();

        return response()->noContent();
    }

    public function criteriaIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = EvaluationCriteria::query()->orderBy('display_order');

        if ($cycleId = $request->string('cycle_id')->toString()) {
            $query->where('cycle_id', $cycleId);
        }

        return PaginatedResponse::make($query->paginate($perPage), EvaluationCriteriaResource::class);
    }

    public function criteriaStore(CriteriaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $criteria = EvaluationCriteria::query()->create([
            'id' => (string) Str::uuid(),
            'cycle_id' => $data['cycleId'],
            'name' => $data['name'],
            'weight' => $data['weight'],
            'description' => $data['description'] ?? null,
            'display_order' => $data['displayOrder'] ?? 0,
            'created_at' => now(),
        ]);

        return (new EvaluationCriteriaResource($criteria))->response()->setStatusCode(201);
    }

    public function criteriaUpdate(CriteriaRequest $request, EvaluationCriteria $criterio): EvaluationCriteriaResource
    {
        $data = $request->validated();
        $criterio->update([
            'cycle_id' => $data['cycleId'],
            'name' => $data['name'],
            'weight' => $data['weight'],
            'description' => $data['description'] ?? null,
            'display_order' => $data['displayOrder'] ?? $criterio->display_order,
        ]);

        return new EvaluationCriteriaResource($criterio);
    }

    public function criteriaDestroy(EvaluationCriteria $criterio): Response
    {
        $criterio->delete();

        return response()->noContent();
    }

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Evaluation::query()->with('cycle')->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('strengths', 'like', "%{$search}%")
                ->orWhere('general_comments', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($cycleId = $request->string('cycle_id')->toString()) {
            $query->where('cycle_id', $cycleId);
        }
        if ($employeeId = $request->string('employee_id')->toString()) {
            $query->where('employee_id', $employeeId);
        }

        return PaginatedResponse::make($query->paginate($perPage), EvaluationResource::class);
    }

    public function show(Evaluation $avaliacao): EvaluationResource
    {
        return new EvaluationResource($avaliacao->load('cycle'));
    }

    public function store(EvaluationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $now = now();
        $evaluation = Evaluation::query()->create([
            'id' => (string) Str::uuid(),
            'cycle_id' => $data['cycleId'],
            'employee_id' => $data['employeeId'],
            'evaluator_id' => $data['evaluatorId'] ?? null,
            'evaluation_date' => $data['evaluationDate'] ?? null,
            'global_score' => $data['globalScore'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvements' => $data['improvements'] ?? null,
            'general_comments' => $data['generalComments'] ?? null,
            'status' => 'rascunho',
            'created_at' => $now,
        ]);

        EvaluationHistory::query()->create([
            'id' => (string) Str::uuid(),
            'evaluation_id' => $evaluation->id,
            'actor_id' => null,
            'action' => 'criou',
            'from_status' => null,
            'to_status' => 'rascunho',
            'comment' => null,
            'changes' => null,
            'created_at' => $now,
        ]);

        return (new EvaluationResource($evaluation->load('cycle')))->response()->setStatusCode(201);
    }

    public function update(EvaluationRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        $data = $request->validated();
        $avaliacao->update([
            'cycle_id' => $data['cycleId'],
            'employee_id' => $data['employeeId'],
            'evaluator_id' => $data['evaluatorId'] ?? null,
            'evaluation_date' => $data['evaluationDate'] ?? null,
            'global_score' => $data['globalScore'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvements' => $data['improvements'] ?? null,
            'general_comments' => $data['generalComments'] ?? null,
        ]);

        return new EvaluationResource($avaliacao->load('cycle'));
    }

    public function destroy(Evaluation $avaliacao): Response
    {
        $avaliacao->delete();

        return response()->noContent();
    }

    public function scores(Evaluation $avaliacao): AnonymousResourceCollection
    {
        return EvaluationScoreResource::collection($avaliacao->scores);
    }

    public function history(Evaluation $avaliacao): AnonymousResourceCollection
    {
        return EvaluationHistoryResource::collection($avaliacao->history);
    }

    // O actor de cada transição é SEMPRE `$request->user()->id` — nunca um
    // `actorId` do payload (era forjável, ver EvaluationTransitionRequest).
    public function submit(EvaluationTransitionRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        return new EvaluationResource($this->workflow->submeter($avaliacao, $request->user()->id)->load('cycle'));
    }

    public function approve(EvaluationTransitionRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        return new EvaluationResource($this->workflow->aprovar($avaliacao, $request->user()->id)->load('cycle'));
    }

    public function reject(EvaluationRejectRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        $data = $request->validated();

        return new EvaluationResource($this->workflow->rejeitar($avaliacao, $request->user()->id, $data['reason'])->load('cycle'));
    }

    public function validate(EvaluationTransitionRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        return new EvaluationResource($this->workflow->validar($avaliacao, $request->user()->id)->load('cycle'));
    }

    public function reopen(EvaluationTransitionRequest $request, Evaluation $avaliacao): EvaluationResource
    {
        return new EvaluationResource($this->workflow->reabrir($avaliacao, $request->user()->id)->load('cycle'));
    }
}
