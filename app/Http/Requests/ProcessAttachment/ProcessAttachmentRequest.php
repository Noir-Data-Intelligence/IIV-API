<?php

namespace App\Http\Requests\ProcessAttachment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'documentId' => ['nullable', 'uuid', 'exists:documentos,id'],
            'filePath' => ['nullable', 'string'],
            'fileName' => ['nullable', 'string'],
            'fileSize' => ['nullable', 'integer', 'min:0'],
            'mimeType' => ['nullable', 'string'],
            'uploadedBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
