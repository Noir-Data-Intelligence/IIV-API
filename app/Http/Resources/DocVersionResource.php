<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocVersionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'documentId' => $this->document_id,
            'versionNumber' => $this->version_number,
            'filePath' => $this->file_path,
            'fileName' => $this->file_name,
            'fileSize' => $this->file_size,
            'mimeType' => $this->mime_type,
            'changeNotes' => $this->change_notes,
            'uploadedBy' => $this->uploaded_by,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
