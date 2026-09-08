<?php

namespace App\Http\Requests\RhTransversal;

use Illuminate\Foundation\Http\FormRequest;

class RhColaboradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employeeNumber' => ['required', 'string', 'max:50'],
            'fullName' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'nationalId' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'hireDate' => ['nullable', 'date'],
            'isActive' => ['sometimes', 'boolean'],
            'qualifications' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
