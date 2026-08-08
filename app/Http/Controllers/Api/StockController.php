<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockItemRequest;
use App\Http\Requests\Stock\StockLocationRequest;
use App\Http\Requests\Stock\StockMovementRequest;
use App\Http\Resources\StockItemResource;
use App\Http\Resources\StockLocationResource;
use App\Http\Resources\StockMovementResource;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class StockController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly StockMovementService $movements) {}

    public function itensIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = StockItem::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('supplier', 'like', "%{$search}%"));
        }
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }
        if ($locationId = $request->string('location_id')->toString()) {
            $query->where('location_id', $locationId);
        }

        return PaginatedResponse::make($query->paginate($perPage), StockItemResource::class);
    }

    public function itensStore(StockItemRequest $request): JsonResponse
    {
        $item = StockItem::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->itemToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new StockItemResource($item))->response()->setStatusCode(201);
    }

    public function itensUpdate(StockItemRequest $request, StockItem $item): StockItemResource
    {
        $item->update($this->itemToColumns($request->validated()));

        return new StockItemResource($item);
    }

    public function itensDestroy(StockItem $item): Response
    {
        $item->delete();

        return response()->noContent();
    }

    public function localizacoesIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = StockLocation::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return PaginatedResponse::make($query->paginate($perPage), StockLocationResource::class);
    }

    public function localizacoesStore(StockLocationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $location = StockLocation::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['isActive'] ?? true,
            'created_at' => now(),
        ]);

        return (new StockLocationResource($location))->response()->setStatusCode(201);
    }

    public function localizacoesUpdate(StockLocationRequest $request, StockLocation $localizacao): StockLocationResource
    {
        $data = $request->validated();
        $localizacao->update(array_filter([
            'name' => $data['name'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : $localizacao->description,
            'is_active' => $data['isActive'] ?? null,
        ], fn ($v) => $v !== null));

        return new StockLocationResource($localizacao);
    }

    public function localizacoesDestroy(StockLocation $localizacao): Response
    {
        $localizacao->delete();

        return response()->noContent();
    }

    public function movimentosIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = StockMovement::query()->orderByDesc('movement_date');

        if ($search = $request->string('search')->toString()) {
            $query->where('reason', 'like', "%{$search}%");
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($itemId = $request->string('item_id')->toString()) {
            $query->where('item_id', $itemId);
        }

        return PaginatedResponse::make($query->paginate($perPage), StockMovementResource::class);
    }

    public function movimentosStore(StockMovementRequest $request): JsonResponse
    {
        $movimento = $this->movements->registar($this->movementToColumns($request->validated()));

        return (new StockMovementResource($movimento))->response()->setStatusCode(201);
    }

    public function movimentosDestroy(StockMovement $movimento): Response
    {
        $this->movements->reverter($movimento);

        return response()->noContent();
    }

    private function itemToColumns(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'laboratorio',
            'sku' => $validated['sku'] ?? null,
            'unit' => $validated['unit'] ?? 'unidade',
            'quantity' => $validated['quantity'] ?? 0,
            'min_stock' => $validated['minStock'] ?? 0,
            'location_id' => $validated['locationId'] ?? null,
            'expiry_date' => $validated['expiryDate'] ?? null,
            'supplier' => $validated['supplier'] ?? null,
            'unit_cost' => $validated['unitCost'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function movementToColumns(array $validated): array
    {
        return [
            'item_id' => $validated['itemId'],
            'type' => $validated['type'] ?? 'entrada',
            'quantity' => $validated['quantity'],
            'from_location_id' => $validated['fromLocationId'] ?? null,
            'to_location_id' => $validated['toLocationId'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'performed_by' => $validated['performedBy'] ?? null,
            'movement_date' => $validated['movementDate'] ?? now()->toDateString(),
        ];
    }
}
