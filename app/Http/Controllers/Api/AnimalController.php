<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Animal\AnimalEventRequest;
use App\Http\Requests\Animal\AnimalHealthRecordRequest;
use App\Http\Requests\Animal\AnimalRequest;
use App\Http\Resources\AnimalEventResource;
use App\Http\Resources\AnimalResource;
use App\Http\Resources\HealthRecordResource;
use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\AnimalHealthRecord;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AnimalController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Animal::query()->orderBy('tag');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('species', 'like', "%{$search}%"));
        }
        if ($stationId = $request->string('station_id')->toString()) {
            $query->where('station_id', $stationId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($species = $request->string('species')->toString()) {
            $query->where('species', $species);
        }

        return PaginatedResponse::make($query->paginate($perPage), AnimalResource::class);
    }

    public function store(AnimalRequest $request): JsonResponse
    {
        $animal = Animal::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new AnimalResource($animal))->response()->setStatusCode(201);
    }

    public function update(AnimalRequest $request, Animal $animal): AnimalResource
    {
        $animal->update($this->toColumns($request->validated()));

        return new AnimalResource($animal);
    }

    public function destroy(Animal $animal): Response
    {
        $animal->delete();

        return response()->noContent();
    }

    public function events(Animal $animal): AnonymousResourceCollection
    {
        return AnimalEventResource::collection($animal->events);
    }

    public function storeEvent(AnimalEventRequest $request, Animal $animal): JsonResponse
    {
        $data = $request->validated();
        $event = AnimalEvent::query()->create([
            'id' => (string) Str::uuid(),
            'animal_id' => $animal->id,
            'event_type' => $data['eventType'] ?? 'observacao',
            'event_date' => $data['eventDate'],
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return (new AnimalEventResource($event))->response()->setStatusCode(201);
    }

    public function healthRecords(Animal $animal): AnonymousResourceCollection
    {
        return HealthRecordResource::collection($animal->healthRecords);
    }

    public function storeHealthRecord(AnimalHealthRecordRequest $request, Animal $animal): JsonResponse
    {
        $data = $request->validated();
        $record = AnimalHealthRecord::query()->create([
            'id' => (string) Str::uuid(),
            'animal_id' => $animal->id,
            'record_type' => $data['recordType'] ?? 'vacina',
            'product_name' => $data['productName'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? null,
            'treatment' => $data['treatment'] ?? null,
            'veterinarian' => $data['veterinarian'] ?? null,
            'record_date' => $data['recordDate'],
            'next_due_date' => $data['nextDueDate'] ?? null,
            'created_at' => now(),
        ]);

        return (new HealthRecordResource($record))->response()->setStatusCode(201);
    }

    private function toColumns(array $validated): array
    {
        return [
            'station_id' => $validated['stationId'],
            'tag' => $validated['tag'] ?? 'SEM-TAG',
            'name' => $validated['name'] ?? null,
            'species' => $validated['species'],
            'breed' => $validated['breed'] ?? null,
            'sex' => $validated['sex'] ?? 'femea',
            'birth_date' => $validated['birthDate'] ?? null,
            'mother_tag' => $validated['motherTag'] ?? null,
            'father_tag' => $validated['fatherTag'] ?? null,
            'status' => $validated['status'] ?? 'activo',
            'current_weight_kg' => $validated['currentWeightKg'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
