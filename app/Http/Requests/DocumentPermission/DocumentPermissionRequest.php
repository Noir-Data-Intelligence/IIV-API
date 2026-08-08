<?php

namespace App\Http\Requests\DocumentPermission;

use Illuminate\Foundation\Http\FormRequest;

class DocumentPermissionRequest extends FormRequest
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
            'role' => ['nullable', 'string'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'canEdit' => ['required', 'boolean'],
        ];
    }
}
