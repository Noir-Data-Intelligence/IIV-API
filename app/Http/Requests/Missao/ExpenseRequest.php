<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['transporte', 'alojamento', 'alimentacao', 'combustivel', 'outro'])],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'expenseDate' => ['required', 'date'],
            'receiptUrl' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
