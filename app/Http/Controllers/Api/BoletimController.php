<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Boletim\ResultadoRequest;
use App\Http\Resources\BoletimInternoResource;
use App\Models\BoletimInterno;
use App\Services\LabWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;

class BoletimController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly LabWorkflowService $workflow) {}

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = BoletimInterno::query()->with('boletimAnalise')->orderByDesc('entrada_em');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), BoletimInternoResource::class);
    }

    public function show(BoletimInterno $boletim): BoletimInternoResource
    {
        return new BoletimInternoResource($boletim->load('boletimAnalise'));
    }

    public function registarResultado(ResultadoRequest $request, BoletimInterno $boletim): BoletimInternoResource
    {
        $this->workflow->registarResultado($boletim, $request->validated()['resultado']);

        return new BoletimInternoResource($boletim->refresh()->load('boletimAnalise'));
    }

    public function validar(Request $request, BoletimInterno $boletim): BoletimInternoResource
    {
        $boletimAnalise = $boletim->boletimAnalise()->firstOrFail();

        $this->workflow->validar($boletimAnalise, $request->user());

        return new BoletimInternoResource($boletim->refresh()->load('boletimAnalise'));
    }

    public function comunicar(BoletimInterno $boletim): BoletimInternoResource
    {
        $this->workflow->comunicar($boletim);

        return new BoletimInternoResource($boletim->refresh()->load('boletimAnalise'));
    }
}
