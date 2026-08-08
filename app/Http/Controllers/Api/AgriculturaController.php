<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agricultura\CropRequest;
use App\Http\Requests\Agricultura\FieldRequest;
use App\Http\Requests\Agricultura\HarvestRequest;
use App\Http\Resources\CropResource;
use App\Http\Resources\FieldResource;
use App\Http\Resources\HarvestResource;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Harvest;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AgriculturaController extends Controller
{
    use PaginatesRequests;

    public function culturasIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Crop::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return PaginatedResponse::make($query->paginate($perPage), CropResource::class);
    }

    public function culturasStore(CropRequest $request): JsonResponse
    {
        $data = $request->validated();
        $crop = Crop::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'scientific_name' => $data['scientificName'] ?? null,
            'cycle_days' => $data['cycleDays'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new CropResource($crop))->response()->setStatusCode(201);
    }

    public function culturasUpdate(CropRequest $request, Crop $cultura): CropResource
    {
        $data = $request->validated();
        $cultura->update([
            'name' => $data['name'],
            'scientific_name' => $data['scientificName'] ?? null,
            'cycle_days' => $data['cycleDays'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return new CropResource($cultura);
    }

    public function culturasDestroy(Crop $cultura): Response
    {
        $cultura->delete();

        return response()->noContent();
    }

    public function camposIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Field::query()->orderByDesc('planting_date');

        if ($search = $request->string('search')->toString()) {
            $query->where('field_code', 'like', "%{$search}%");
        }
        if ($stationId = $request->string('station_id')->toString()) {
            $query->where('station_id', $stationId);
        }
        if ($cropId = $request->string('crop_id')->toString()) {
            $query->where('crop_id', $cropId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), FieldResource::class);
    }

    public function camposStore(FieldRequest $request): JsonResponse
    {
        $field = Field::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->fieldToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new FieldResource($field))->response()->setStatusCode(201);
    }

    public function camposUpdate(FieldRequest $request, Field $campo): FieldResource
    {
        $campo->update($this->fieldToColumns($request->validated()));

        return new FieldResource($campo);
    }

    public function camposDestroy(Field $campo): Response
    {
        $campo->delete();

        return response()->noContent();
    }

    public function colheitasIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Harvest::query()->orderByDesc('harvest_date');

        if ($search = $request->string('search')->toString()) {
            $query->where('quality_grade', 'like', "%{$search}%");
        }
        if ($fieldId = $request->string('field_id')->toString()) {
            $query->where('field_id', $fieldId);
        }

        return PaginatedResponse::make($query->paginate($perPage), HarvestResource::class);
    }

    public function colheitasStore(HarvestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $harvest = Harvest::query()->create([
            'id' => (string) Str::uuid(),
            'field_id' => $data['fieldId'],
            'harvest_date' => $data['harvestDate'] ?? now()->toDateString(),
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? 'kg',
            'quality_grade' => $data['qualityGrade'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new HarvestResource($harvest))->response()->setStatusCode(201);
    }

    public function colheitasUpdate(HarvestRequest $request, Harvest $colheita): HarvestResource
    {
        $data = $request->validated();
        $colheita->update([
            'field_id' => $data['fieldId'],
            'harvest_date' => $data['harvestDate'] ?? $colheita->harvest_date,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? $colheita->unit,
            'quality_grade' => $data['qualityGrade'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return new HarvestResource($colheita);
    }

    public function colheitasDestroy(Harvest $colheita): Response
    {
        $colheita->delete();

        return response()->noContent();
    }

    private function fieldToColumns(array $validated): array
    {
        return [
            'station_id' => $validated['stationId'],
            'crop_id' => $validated['cropId'],
            'field_code' => $validated['fieldCode'] ?? null,
            'area_ha' => $validated['areaHa'] ?? 0,
            'planting_date' => $validated['plantingDate'] ?? null,
            'expected_harvest' => $validated['expectedHarvest'] ?? null,
            'status' => $validated['status'] ?? 'planeado',
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
