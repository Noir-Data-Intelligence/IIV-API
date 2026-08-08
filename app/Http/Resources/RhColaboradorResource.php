<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RhColaboradorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employeeNumber' => $this->employee_number,
            'fullName' => $this->full_name,
            'nationalId' => $this->national_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'departmentId' => $this->department_id,
            'hireDate' => $this->hire_date?->toDateString(),
            'isActive' => (bool) $this->is_active,
            'qualifications' => $this->qualifications,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
