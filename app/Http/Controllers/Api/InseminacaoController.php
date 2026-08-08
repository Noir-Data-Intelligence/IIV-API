<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inseminacao\BreederRequest;
use App\Http\Requests\Inseminacao\IaCenterRequest;
use App\Http\Requests\Inseminacao\InseminationRecordRequest;
use App\Http\Requests\Inseminacao\NitrogenTankRequest;
use App\Http\Requests\Inseminacao\SemenDoseRequest;
use App\Http\Resources\BreederResource;
use App\Http\Resources\DoseResource;
use App\Http\Resources\IaCenterResource;
use App\Http\Resources\InseminationRecordResource;
use App\Http\Resources\TankResource;
use App\Models\Breeder;
use App\Models\IaCenter;
use App\Models\InseminationRecord;
use App\Models\NitrogenTank;
use App\Models\SemenDose;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Módulo de Inseminação Artificial (Onda 9) — o único deste projecto sem
 * envelope de paginação: `mocks/handlers/inseminacao.ts` devolve arrays
 * simples via uma fábrica genérica `crudHandlers<T>`, ao contrário de todos
 * os outros recursos, que usam `Paginated<T>`.
 */
class InseminacaoController extends Controller
{
    public function centros(): AnonymousResourceCollection
    {
        return IaCenterResource::collection(IaCenter::query()->orderBy('name')->get());
    }

    public function storeCentro(IaCenterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $centro = IaCenter::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'responsible_user_id' => $data['responsibleUserId'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['isActive'] ?? true,
            'created_at' => now(),
        ]);

        return (new IaCenterResource($centro))->response()->setStatusCode(201);
    }

    public function updateCentro(IaCenterRequest $request, IaCenter $centro): IaCenterResource
    {
        $data = $request->validated();
        $centro->update([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'responsible_user_id' => $data['responsibleUserId'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['isActive'] ?? $centro->is_active,
        ]);

        return new IaCenterResource($centro);
    }

    public function destroyCentro(IaCenter $centro): Response
    {
        $centro->delete();

        return response()->noContent();
    }

    public function reprodutores(): AnonymousResourceCollection
    {
        return BreederResource::collection(Breeder::query()->orderBy('tag')->get());
    }

    public function storeReprodutor(BreederRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reprodutor = Breeder::query()->create([
            'id' => (string) Str::uuid(),
            'center_id' => $data['centerId'],
            'tag' => $data['tag'] ?? 'SEM-TAG',
            'name' => $data['name'] ?? null,
            'species' => $data['species'],
            'breed' => $data['breed'] ?? null,
            'birth_date' => $data['birthDate'] ?? null,
            'status' => $data['status'] ?? 'activo',
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new BreederResource($reprodutor))->response()->setStatusCode(201);
    }

    public function updateReprodutor(BreederRequest $request, Breeder $reprodutor): BreederResource
    {
        $data = $request->validated();
        $reprodutor->update([
            'center_id' => $data['centerId'],
            'tag' => $data['tag'] ?? $reprodutor->tag,
            'name' => $data['name'] ?? null,
            'species' => $data['species'],
            'breed' => $data['breed'] ?? null,
            'birth_date' => $data['birthDate'] ?? null,
            'status' => $data['status'] ?? $reprodutor->status,
            'notes' => $data['notes'] ?? null,
        ]);

        return new BreederResource($reprodutor);
    }

    public function destroyReprodutor(Breeder $reprodutor): Response
    {
        $reprodutor->delete();

        return response()->noContent();
    }

    public function tanques(): AnonymousResourceCollection
    {
        return TankResource::collection(NitrogenTank::query()->orderBy('code')->get());
    }

    public function storeTanque(NitrogenTankRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tanque = NitrogenTank::query()->create([
            'id' => (string) Str::uuid(),
            'center_id' => $data['centerId'],
            'code' => $data['code'] ?? 'SEM-COD',
            'capacity_l' => $data['capacityL'] ?? 0,
            'current_level_l' => $data['currentLevelL'] ?? 0,
            'min_level_l' => $data['minLevelL'] ?? 0,
            'last_refill_date' => $data['lastRefillDate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new TankResource($tanque))->response()->setStatusCode(201);
    }

    public function updateTanque(NitrogenTankRequest $request, NitrogenTank $tanque): TankResource
    {
        $data = $request->validated();
        $tanque->update([
            'center_id' => $data['centerId'],
            'code' => $data['code'] ?? $tanque->code,
            'capacity_l' => $data['capacityL'] ?? $tanque->capacity_l,
            'current_level_l' => $data['currentLevelL'] ?? $tanque->current_level_l,
            'min_level_l' => $data['minLevelL'] ?? $tanque->min_level_l,
            'last_refill_date' => $data['lastRefillDate'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return new TankResource($tanque);
    }

    public function destroyTanque(NitrogenTank $tanque): Response
    {
        $tanque->delete();

        return response()->noContent();
    }

    public function doses(): AnonymousResourceCollection
    {
        return DoseResource::collection(SemenDose::query()->orderByDesc('collection_date')->get());
    }

    public function storeDose(SemenDoseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $dose = SemenDose::query()->create([
            'id' => (string) Str::uuid(),
            'breeder_id' => $data['breederId'],
            'tank_id' => $data['tankId'] ?? null,
            'collection_date' => $data['collectionDate'],
            'quantity' => $data['quantity'],
            'available_quantity' => $data['availableQuantity'] ?? $data['quantity'],
            'quality_grade' => $data['qualityGrade'] ?? 'A',
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new DoseResource($dose))->response()->setStatusCode(201);
    }

    public function updateDose(SemenDoseRequest $request, SemenDose $dose): DoseResource
    {
        $data = $request->validated();
        $dose->update([
            'breeder_id' => $data['breederId'],
            'tank_id' => $data['tankId'] ?? null,
            'collection_date' => $data['collectionDate'],
            'quantity' => $data['quantity'],
            'available_quantity' => $data['availableQuantity'] ?? $dose->available_quantity,
            'quality_grade' => $data['qualityGrade'] ?? $dose->quality_grade,
            'notes' => $data['notes'] ?? null,
        ]);

        return new DoseResource($dose);
    }

    public function destroyDose(SemenDose $dose): Response
    {
        $dose->delete();

        return response()->noContent();
    }

    public function registos(): AnonymousResourceCollection
    {
        return InseminationRecordResource::collection(InseminationRecord::query()->orderByDesc('insemination_date')->get());
    }

    public function storeRegisto(InseminationRecordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $registo = InseminationRecord::query()->create([
            'id' => (string) Str::uuid(),
            'animal_id' => $data['animalId'],
            'dose_id' => $data['doseId'] ?? null,
            'technician_id' => $data['technicianId'] ?? null,
            'insemination_date' => $data['inseminationDate'],
            'result' => $data['result'] ?? 'pendente',
            'pregnancy_confirmed_at' => $data['pregnancyConfirmedAt'] ?? null,
            'expected_birth_date' => $data['expectedBirthDate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new InseminationRecordResource($registo))->response()->setStatusCode(201);
    }

    public function updateRegisto(InseminationRecordRequest $request, InseminationRecord $registo): InseminationRecordResource
    {
        $data = $request->validated();
        $registo->update([
            'animal_id' => $data['animalId'],
            'dose_id' => $data['doseId'] ?? null,
            'technician_id' => $data['technicianId'] ?? null,
            'insemination_date' => $data['inseminationDate'],
            'result' => $data['result'] ?? $registo->result,
            'pregnancy_confirmed_at' => $data['pregnancyConfirmedAt'] ?? null,
            'expected_birth_date' => $data['expectedBirthDate'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return new InseminationRecordResource($registo);
    }

    public function destroyRegisto(InseminationRecord $registo): Response
    {
        $registo->delete();

        return response()->noContent();
    }
}
