<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\RhLaboratorio\RhAusenciaRequest;
use App\Http\Requests\RhLaboratorio\RhColaboradorRequest;
use App\Http\Requests\RhLaboratorio\RhContratoRequest;
use App\Http\Resources\RhAusenciaResource;
use App\Http\Resources\RhColaboradorResource;
use App\Http\Resources\RhContratoResource;
use App\Models\RhLaboratorioAusencia;
use App\Models\RhLaboratorioColaborador;
use App\Models\RhLaboratorioContrato;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * RH laboratorial — esquema separado de RhTransversalController (dualidade
 * obrigatória, ver SIG-IIV-MEMORIA-PROJETO.md secção 6).
 */
class RhLaboratorioController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = RhLaboratorioColaborador::query()->orderBy('full_name');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('employee_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }
        if ($departmentId = $request->string('department_id')->toString()) {
            $query->where('department_id', $departmentId);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return PaginatedResponse::make($query->paginate($perPage), RhColaboradorResource::class);
    }

    public function store(RhColaboradorRequest $request): JsonResponse
    {
        $colaborador = RhLaboratorioColaborador::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->colaboradorToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new RhColaboradorResource($colaborador))->response()->setStatusCode(201);
    }

    public function update(RhColaboradorRequest $request, RhLaboratorioColaborador $colaborador): RhColaboradorResource
    {
        $colaborador->update($this->colaboradorToColumns($request->validated()));

        return new RhColaboradorResource($colaborador);
    }

    public function destroy(RhLaboratorioColaborador $colaborador): Response
    {
        $colaborador->delete();

        return response()->noContent();
    }

    public function contratosIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = RhLaboratorioContrato::query()->orderByDesc('start_date');

        if ($search = $request->string('search')->toString()) {
            $query->where('position', 'like', "%{$search}%");
        }
        if ($employeeId = $request->string('employee_id')->toString()) {
            $query->where('employee_id', $employeeId);
        }
        if ($contractType = $request->string('contract_type')->toString()) {
            $query->where('contract_type', $contractType);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return PaginatedResponse::make($query->paginate($perPage), RhContratoResource::class);
    }

    public function contratosStore(RhContratoRequest $request): JsonResponse
    {
        $contrato = RhLaboratorioContrato::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->contratoToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new RhContratoResource($contrato))->response()->setStatusCode(201);
    }

    public function contratosUpdate(RhContratoRequest $request, RhLaboratorioContrato $contrato): RhContratoResource
    {
        $contrato->update($this->contratoToColumns($request->validated()));

        return new RhContratoResource($contrato);
    }

    public function contratosDestroy(RhLaboratorioContrato $contrato): Response
    {
        $contrato->delete();

        return response()->noContent();
    }

    public function ausenciasIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = RhLaboratorioAusencia::query()->orderByDesc('start_date');

        if ($employeeId = $request->string('employee_id')->toString()) {
            $query->where('employee_id', $employeeId);
        }
        if ($leaveType = $request->string('leave_type')->toString()) {
            $query->where('leave_type', $leaveType);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), RhAusenciaResource::class);
    }

    public function ausenciasStore(RhAusenciaRequest $request): JsonResponse
    {
        $ausencia = RhLaboratorioAusencia::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->ausenciaToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new RhAusenciaResource($ausencia))->response()->setStatusCode(201);
    }

    public function ausenciasUpdate(RhAusenciaRequest $request, RhLaboratorioAusencia $ausencia): RhAusenciaResource
    {
        $ausencia->update($this->ausenciaToColumns($request->validated()));

        return new RhAusenciaResource($ausencia);
    }

    public function ausenciasDestroy(RhLaboratorioAusencia $ausencia): Response
    {
        $ausencia->delete();

        return response()->noContent();
    }

    private function colaboradorToColumns(array $validated): array
    {
        return [
            'employee_number' => $validated['employeeNumber'],
            'full_name' => $validated['fullName'],
            'position' => $validated['position'] ?? null,
            'national_id' => $validated['nationalId'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'department_id' => $validated['departmentId'] ?? null,
            'hire_date' => $validated['hireDate'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
            'qualifications' => $validated['qualifications'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function contratoToColumns(array $validated): array
    {
        return [
            'employee_id' => $validated['employeeId'],
            'contract_type' => $validated['contractType'],
            'position' => $validated['position'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'] ?? null,
            'salary' => $validated['salary'],
            'currency' => $validated['currency'] ?? 'AOA',
            'is_active' => $validated['isActive'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function ausenciaToColumns(array $validated): array
    {
        return [
            'employee_id' => $validated['employeeId'],
            'leave_type' => $validated['leaveType'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'days' => $validated['days'] ?? $this->diasEntre($validated['startDate'], $validated['endDate']),
            'status' => $validated['status'] ?? 'pendente',
            'reason' => $validated['reason'] ?? null,
        ];
    }

    private function diasEntre(string $inicio, string $fim): int
    {
        return Carbon::parse($inicio)->diffInDays(Carbon::parse($fim)) + 1;
    }
}
