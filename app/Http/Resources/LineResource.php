<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'area' => $this->area,
            'description' => $this->description,
            'status' => $this->status,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
