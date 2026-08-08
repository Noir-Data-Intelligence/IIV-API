<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'productType' => $this->product_type?->value,
            'description' => $this->description,
            'unit' => $this->unit,
            'isArchived' => (bool) $this->is_archived,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
