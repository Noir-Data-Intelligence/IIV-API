<?php

namespace App\Http\Requests\ProcessTypeStep;

use App\Enums\AppRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessTypeStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'orderIndex' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'defaultRole' => ['nullable', Rule::in(AppRole::values())],
            'slaDays' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
