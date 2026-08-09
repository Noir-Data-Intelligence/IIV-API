<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investigacao\LineRequest;
use App\Http\Requests\Investigacao\ProjectRequest;
use App\Http\Requests\Investigacao\PublicationRequest;
use App\Http\Resources\LineResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\PublicationResource;
use App\Models\Publication;
use App\Models\ResearchLine;
use App\Models\ResearchProject;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class InvestigacaoController extends Controller
{
    use PaginatesRequests;

    public function stats(): JsonResponse
    {
        return response()->json([
            'totalLines' => ResearchLine::query()->count(),
            'activeProjects' => ResearchProject::query()->whereIn('status', ['em_curso', 'aprovado'])->count(),
            'totalPublications' => Publication::query()->count(),
            'totalFunding' => (float) ResearchProject::query()->where('status', '!=', 'cancelado')->sum('funding_amount'),
        ]);
    }

    public function linesIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = ResearchLine::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), LineResource::class);
    }

    public function linesStore(LineRequest $request): JsonResponse
    {
        $data = $request->validated();
        $line = ResearchLine::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'area' => $data['area'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'activa',
            'created_by' => $data['createdBy'] ?? null,
            'created_at' => now(),
        ]);

        return (new LineResource($line))->response()->setStatusCode(201);
    }

    public function linesUpdate(LineRequest $request, ResearchLine $linha): LineResource
    {
        $data = $request->validated();
        $linha->update([
            'name' => $data['name'],
            'area' => $data['area'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? $linha->status,
        ]);

        return new LineResource($linha);
    }

    public function linesDestroy(ResearchLine $linha): Response
    {
        $linha->delete();

        return response()->noContent();
    }

    public function projectsIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = ResearchProject::query()->orderByDesc('created_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('objectives', 'like', "%{$search}%")
                ->orWhere('funding_source', 'like', "%{$search}%")
                ->orWhere('partners', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($lineId = $request->string('line_id')->toString()) {
            $query->where('line_id', $lineId);
        }

        return PaginatedResponse::make($query->paginate($perPage), ProjectResource::class);
    }

    public function projectsStore(ProjectRequest $request): JsonResponse
    {
        $project = ResearchProject::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->projectToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function projectsUpdate(ProjectRequest $request, ResearchProject $projecto): ProjectResource
    {
        $projecto->update($this->projectToColumns($request->validated()));

        return new ProjectResource($projecto);
    }

    public function projectsDestroy(ResearchProject $projecto): Response
    {
        $projecto->delete();

        return response()->noContent();
    }

    public function publicationsIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Publication::query()->orderByDesc('year');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('venue', 'like', "%{$search}%")
                ->orWhere('doi', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($year = $request->string('year')->toString()) {
            $query->where('year', $year);
        }
        if ($projectId = $request->string('project_id')->toString()) {
            $query->where('project_id', $projectId);
        }

        return PaginatedResponse::make($query->paginate($perPage), PublicationResource::class);
    }

    public function publicationsStore(PublicationRequest $request): JsonResponse
    {
        $publication = Publication::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->publicationToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new PublicationResource($publication))->response()->setStatusCode(201);
    }

    public function publicationsUpdate(PublicationRequest $request, Publication $publicacao): PublicationResource
    {
        $data = $request->validated();
        $publicacao->update([
            ...$this->publicationToColumns($data),
            'authors' => $data['authors'] ?? $publicacao->authors,
        ]);

        return new PublicationResource($publicacao);
    }

    public function publicationsDestroy(Publication $publicacao): Response
    {
        $publicacao->delete();

        return response()->noContent();
    }

    private function projectToColumns(array $validated): array
    {
        return [
            'line_id' => $validated['lineId'] ?? null,
            'title' => $validated['title'],
            'objectives' => $validated['objectives'] ?? null,
            'status' => $validated['status'] ?? 'proposto',
            'start_date' => $validated['startDate'] ?? null,
            'end_date' => $validated['endDate'] ?? null,
            'funding_source' => $validated['fundingSource'] ?? null,
            'funding_amount' => $validated['fundingAmount'] ?? null,
            'currency' => $validated['currency'] ?? 'AOA',
            'partners' => $validated['partners'] ?? null,
            'created_by' => $validated['createdBy'] ?? null,
        ];
    }

    private function publicationToColumns(array $validated): array
    {
        return [
            'project_id' => $validated['projectId'] ?? null,
            'type' => $validated['type'] ?? 'artigo',
            'title' => $validated['title'],
            'authors' => $validated['authors'] ?? [],
            'year' => $validated['year'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'doi' => $validated['doi'] ?? null,
            'url' => $validated['url'] ?? null,
            'created_by' => $validated['createdBy'] ?? null,
        ];
    }
}
