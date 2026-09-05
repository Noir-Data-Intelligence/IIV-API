<?php

namespace App\Http\Requests\DocCategory;

use Illuminate\Foundation\Http\FormRequest;

class DocCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
