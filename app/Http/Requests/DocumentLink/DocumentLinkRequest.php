<?php

namespace App\Http\Requests\DocumentLink;

use Illuminate\Foundation\Http\FormRequest;

class DocumentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'documentId' => ['required', 'uuid', 'exists:documentos,id'],
            'entityType' => ['required', 'string', 'max:100'],
            'entityId' => ['required', 'uuid'],
        ];
    }
}
