<?php

namespace App\Http\Requests\InqueritoSatisfacao;

use App\Enums\BoletimInternoStatus;
use App\Models\InqueritoSatisfacao;
use App\Models\Requisicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InqueritoSatisfacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'requisicaoId' => ['required', 'uuid', 'exists:requisicoes,id'],
            'dimensoes' => ['required', 'array', 'min:1'],
            'comentario' => ['nullable', 'string'],
        ];
    }

    /**
     * Endpoint público (sem autenticação) — sem estas duas guardas, qualquer
     * pessoa com um UUID de requisição (por exemplo obtido por força bruta,
     * já que `exists:requisicoes` acima confirma quais são válidos) podia
     * inundar o sistema com inquéritos falsos para uma requisição ainda a
     * decorrer, ou repetir o mesmo inquérito indefinidamente (auditoria de
     * segurança, 2026-08-09 — M9).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $requisicaoId = $this->input('requisicaoId');
            if (! $requisicaoId || $validator->errors()->has('requisicaoId')) {
                return;
            }

            $requisicao = Requisicao::query()->with('amostras.boletimInterno', 'boletinsInternos')->find($requisicaoId);
            if (! $requisicao) {
                return;
            }

            // Resultados comunicados = pelo menos um Boletim de Análises emitido
            // pela CGISC (BI em `emitido`); inclui BI antigos (1 por amostra).
            $foiComunicada = $requisicao->boletinsInternos
                ->contains(fn ($bi) => $bi->status === BoletimInternoStatus::Emitido)
                || $requisicao->amostras
                    ->contains(fn ($amostra) => $amostra->boletimInterno?->status === BoletimInternoStatus::Emitido);
            if (! $foiComunicada) {
                $validator->errors()->add(
                    'requisicaoId',
                    'Só é possível avaliar a satisfação depois dos resultados serem comunicados.'
                );

                return;
            }

            $jaRespondido = InqueritoSatisfacao::query()->where('requisicao_id', $requisicaoId)->exists();
            if ($jaRespondido) {
                $validator->errors()->add('requisicaoId', 'Já existe um inquérito de satisfação para esta requisição.');
            }
        });
    }
}
