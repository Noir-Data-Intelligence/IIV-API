<?php

namespace App\Http\Requests\Avaliacao;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `actorId` deixou de vir do payload — ver EvaluationTransitionRequest.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
        ];
    }
}
