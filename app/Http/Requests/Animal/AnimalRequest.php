<?php

namespace App\Http\Requests\Animal;

use App\Models\Animal;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnimalRequest extends FormRequest
{
    /** Valores fechados do campo "Estado Reprodutivo" (exclusivo de fêmeas). */
    public const REPRODUCTIVE_STATUSES = ['vazia', 'cio', 'coberta', 'gestante', 'lactante'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stationId' => ['required', 'uuid', 'exists:estacoes,id'],
            // `tag` (brinco) nunca vem do cliente — é sempre gerado pelo
            // servidor em `AnimalService::gerarBrinco()` (ANL-004).
            'name' => ['nullable', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['sometimes', Rule::in(['macho', 'femea'])],
            // Só faz sentido para fêmeas (CT-012) — reforçado em `withValidator()`
            // abaixo, porque aqui ainda não sabemos o sexo final do animal numa
            // actualização parcial (`sex` é `sometimes`).
            'reproductiveStatus' => ['nullable', Rule::in(self::REPRODUCTIVE_STATUSES)],
            'birthDate' => ['nullable', 'date'],
            'motherTag' => ['nullable', 'string', 'max:100'],
            'fatherTag' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['activo', 'vendido', 'morto', 'abatido', 'transferido'])],
            'currentWeightKg' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->filled('reproductiveStatus')) {
                return;
            }

            /** @var Animal|null $animal */
            $animal = $this->route('animal');
            $sex = $this->input('sex') ?? $animal?->sex;

            if ($sex !== 'femea') {
                $validator->errors()->add(
                    'reproductiveStatus',
                    'O estado reprodutivo só se aplica a animais fêmea.',
                );
            }
        });
    }
}
