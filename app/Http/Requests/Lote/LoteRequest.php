<?php

namespace App\Http\Requests\Lote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('lote')?->id;

        return [
            'batchNumber' => ['required', 'string', 'max:255', 'unique:lotes,batch_number,'.$id],
            'productId' => ['required', 'uuid', 'exists:produtos,id'],
            'quantityProduced' => ['sometimes', 'integer', 'min:0'],
            'quantityDistributed' => ['sometimes', 'integer', 'min:0'],
            'productionDate' => ['required', 'date'],
            'expiryDate' => ['required', 'date', 'after_or_equal:productionDate'],
            'status' => [Rule::in(['planeada', 'em_producao', 'concluida', 'suspensa'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
