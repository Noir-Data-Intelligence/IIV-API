<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'categoryId' => $this->category_id,
            'ownerId' => $this->owner_id,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'expiryDate' => $this->expiry_date?->toDateString(),
            'tags' => $this->tags,
            'currentVersionId' => $this->current_version_id,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
