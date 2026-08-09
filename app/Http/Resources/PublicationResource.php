<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->project_id,
            'type' => $this->type,
            'title' => $this->title,
            'authors' => $this->authors ?? [],
            'year' => $this->year !== null ? (int) $this->year : null,
            'venue' => $this->venue,
            'doi' => $this->doi,
            'url' => $this->url,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
