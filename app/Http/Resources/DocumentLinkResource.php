<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentLinkResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->document;

        return [
            'id' => $this->id,
            'documentId' => $this->document_id,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
            'document' => $document
                ? ['id' => $document->id, 'title' => $document->title, 'currentVersionId' => $document->current_version_id]
                : null,
        ];
    }
}
