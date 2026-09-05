<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Geração do brinco (`tag`) de um Animal — sequencial por ano, único e não
 * editável pelo utilizador, seguindo o mesmo padrão de
 * `LabWorkflowService::gerarNumeroPat()`/`ProcessWorkflowService::gerarCodigo()`
 * (ver auditoria funcional — ANL-004, Identificação de Animais).
 */
class AnimalService
{
    public function gerarBrinco(): string
    {
        $ano = now()->year;
        $count = DB::table('animals')->where('tag', 'like', "IIV-{$ano}-%")->count();

        return sprintf('IIV-%d-%03d', $ano, $count + 1);
    }
}
