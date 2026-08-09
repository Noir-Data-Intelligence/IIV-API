<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Missao\ExpenseRequest;
use App\Http\Requests\Missao\GuideRequest;
use App\Http\Requests\Missao\MissaoRequest;
use App\Http\Requests\Missao\MissionTransitionRequest;
use App\Http\Requests\Missao\ParticipantRequest;
use App\Http\Requests\Missao\ReportRequest;
use App\Http\Resources\MissaoResource;
use App\Http\Resources\MissionExpenseResource;
use App\Http\Resources\MissionGuideResource;
use App\Http\Resources\MissionParticipantResource;
use App\Http\Resources\MissionReportResource;
use App\Models\Missao;
use App\Models\MissionExpense;
use App\Models\MissionGuide;
use App\Models\MissionParticipant;
use App\Models\MissionReport;
use App\Services\MissionWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class MissaoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly MissionWorkflowService $workflow) {}

    public function stats(): JsonResponse
    {
        return response()->json([
            'total' => Missao::query()->count(),
            'emCurso' => Missao::query()->where('status', 'em_curso')->count(),
            'pendentes' => Missao::query()->whereIn('status', ['submetida', 'planeada', 'aprovada'])->count(),
            'totalBudget' => (float) Missao::query()->where('status', '!=', 'cancelada')->sum('budget'),
        ]);
    }

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Missao::query()->orderByDesc('start_date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('destination', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), MissaoResource::class);
    }

    public function show(Missao $missao): MissaoResource
    {
        return new MissaoResource($missao);
    }

    public function store(MissaoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $missao = Missao::query()->create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'],
            'destination' => $data['destination'],
            'purpose' => $data['purpose'] ?? null,
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'status' => 'planeada',
            'budget' => $data['budget'] ?? 0,
            'currency' => $data['currency'] ?? 'AOA',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
            'created_at' => now(),
        ]);

        return (new MissaoResource($missao))->response()->setStatusCode(201);
    }

    public function update(MissaoRequest $request, Missao $missao): MissaoResource
    {
        $data = $request->validated();
        $missao->update(array_filter([
            'title' => $data['title'] ?? null,
            'destination' => $data['destination'] ?? null,
            'purpose' => array_key_exists('purpose', $data) ? $data['purpose'] : $missao->purpose,
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
            'status' => $data['status'] ?? null,
            'budget' => array_key_exists('budget', $data) ? $data['budget'] : $missao->budget,
            'currency' => $data['currency'] ?? null,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $missao->notes,
        ], fn ($v) => $v !== null));

        return new MissaoResource($missao);
    }

    public function destroy(Missao $missao): Response
    {
        $missao->delete();

        return response()->noContent();
    }

    public function submit(MissionTransitionRequest $request, Missao $missao): MissaoResource
    {
        return new MissaoResource($this->workflow->submeter($missao));
    }

    public function approve(MissionTransitionRequest $request, Missao $missao): MissaoResource
    {
        return new MissaoResource($this->workflow->aprovar($missao, $request->user()->id));
    }

    public function reject(MissionTransitionRequest $request, Missao $missao): MissaoResource
    {
        return new MissaoResource($this->workflow->rejeitar($missao));
    }

    public function participants(Missao $missao): AnonymousResourceCollection
    {
        return MissionParticipantResource::collection($missao->participants()->with('user')->get());
    }

    public function storeParticipant(ParticipantRequest $request, Missao $missao): JsonResponse
    {
        $data = $request->validated();
        $participant = MissionParticipant::query()->create([
            'id' => (string) Str::uuid(),
            'mission_id' => $missao->id,
            'user_id' => $data['userId'],
            'role' => $data['role'],
            'per_diem' => $data['perDiem'] ?? 0,
            'created_at' => now(),
        ]);

        return (new MissionParticipantResource($participant->load('user')))->response()->setStatusCode(201);
    }

    public function destroyParticipant(Missao $missao, MissionParticipant $participante): Response
    {
        $participante->delete();

        return response()->noContent();
    }

    public function guide(Missao $missao): MissionGuideResource|JsonResponse
    {
        $guide = $missao->guide;
        if (! $guide) {
            return JsonResponse::fromJsonString('null');
        }

        return new MissionGuideResource($guide);
    }

    public function updateGuide(GuideRequest $request, Missao $missao): MissionGuideResource
    {
        $data = $request->validated();
        $values = [
            'guide_number' => $data['guideNumber'],
            'issue_date' => $data['issueDate'],
            'per_diem' => $data['perDiem'] ?? 0,
            'transport' => $data['transport'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        $guide = $missao->guide;
        if ($guide) {
            $guide->update($values);
        } else {
            $guide = MissionGuide::query()->create([
                'id' => (string) Str::uuid(),
                'mission_id' => $missao->id,
                ...$values,
                'created_at' => now(),
            ]);
            $guide->wasRecentlyCreated = false;
        }

        return new MissionGuideResource($guide);
    }

    public function expenses(Missao $missao): AnonymousResourceCollection
    {
        return MissionExpenseResource::collection($missao->expenses);
    }

    public function storeExpense(ExpenseRequest $request, Missao $missao): JsonResponse
    {
        $data = $request->validated();
        $expense = MissionExpense::query()->create([
            'id' => (string) Str::uuid(),
            'mission_id' => $missao->id,
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AOA',
            'expense_date' => $data['expenseDate'],
            'receipt_url' => $data['receiptUrl'] ?? null,
            'created_at' => now(),
        ]);

        return (new MissionExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function destroyExpense(Missao $missao, MissionExpense $despesa): Response
    {
        $despesa->delete();

        return response()->noContent();
    }

    public function report(Missao $missao): MissionReportResource|JsonResponse
    {
        $report = $missao->report;
        if (! $report) {
            return JsonResponse::fromJsonString('null');
        }

        return new MissionReportResource($report);
    }

    public function updateReport(ReportRequest $request, Missao $missao): MissionReportResource
    {
        $data = $request->validated();
        $values = [
            'report_date' => $data['reportDate'],
            'summary' => $data['summary'] ?? null,
            'outcomes' => $data['outcomes'] ?? null,
            'status' => $data['status'],
            'submitted_by' => $data['submittedBy'] ?? null,
        ];

        $report = $missao->report;
        if ($report) {
            $report->update($values);
        } else {
            $report = MissionReport::query()->create([
                'id' => (string) Str::uuid(),
                'mission_id' => $missao->id,
                ...$values,
                'created_at' => now(),
            ]);
            $report->wasRecentlyCreated = false;
        }

        return new MissionReportResource($report);
    }

    public function approveReport(Request $request, Missao $missao): MissionReportResource
    {
        $report = $missao->report()->firstOrFail();
        $report->update([
            'status' => 'aprovado',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return new MissionReportResource($report);
    }
}
