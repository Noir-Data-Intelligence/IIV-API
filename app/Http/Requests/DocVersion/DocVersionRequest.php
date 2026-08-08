<?php

namespace App\Http\Requests\DocVersion;

use Illuminate\Foundation\Http\FormRequest;

class DocVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'filePath' => ['nullable', 'string'],
            'fileName' => ['nullable', 'string'],
            'fileSize' => ['nullable', 'integer', 'min:0'],
            'mimeType' => ['nullable', 'string'],
            'changeNotes' => ['nullable', 'string'],
            'uploadedBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
