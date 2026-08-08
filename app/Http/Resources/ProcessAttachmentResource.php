<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessAttachmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->document;

        return [
            'id' => $this->id,
            'processId' => $this->process_id,
            'label' => $this->label,
            'filePath' => $this->file_path,
            'documentId' => $this->document_id,
            'uploadedBy' => $this->uploaded_by,
            'createdAt' => $this->created_at?->toISOString(),
            'document' => $document
                ? ['id' => $document->id, 'title' => $document->title, 'currentVersionId' => $document->current_version_id]
                : null,
        ];
    }
}
