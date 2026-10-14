<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migração de dados do modelo antigo (1 BI por amostra, validadores soltos
 * no BA, estados aprovado/comunicado) para o novo modelo por fases. Só usa
 * o query builder (nunca Models) para não depender da evolução futura das
 * classes. Idempotente: só preenche o que ainda estiver vazio, nunca apaga
 * dados.
 *
 *  - BI: requisicao_id / laboratorio_id a partir da amostra->requisição,
 *    linha em `boletim_interno_amostras`, `numero` no formato PAT-###-ANO/AB
 *    (a partir do `numero_analise` antigo) e mapeamento de estados
 *    (aprovado -> validado, comunicado -> emitido).
 *  - BA: requisicao_id / laboratorio_id, linhas em `validacoes` a partir de
 *    validador_1/2/3_id e, se já comunicado, número/emissão/conteúdo.
 *  - Requisição: estado derivado dos BI/amostras e pat_numero = numero
 *    antigo (que já era um PAT).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $estadoBi = ['aprovado' => 'validado', 'comunicado' => 'emitido'];

        foreach (DB::table('boletins_internos')->whereNotNull('amostra_id')->get() as $bi) {
            $amostra = DB::table('amostras')->where('id', $bi->amostra_id)->first();
            if (! $amostra) {
                continue;
            }
            $requisicao = DB::table('requisicoes')->where('id', $amostra->requisicao_id)->first();
            $laboratorio = $requisicao?->laboratorio_id
                ? DB::table('laboratorios')->where('id', $requisicao->laboratorio_id)->first()
                : null;

            $status = $estadoBi[$bi->status] ?? $bi->status;

            $numero = $bi->numero;
            if (! $numero) {
                $numero = $bi->numero_analise.'/'.($laboratorio->code ?? 'XX');
                if (DB::table('boletins_internos')->where('numero', $numero)->exists()) {
                    $numero .= '-'.substr($bi->id, 0, 4);
                }
            }

            DB::table('boletins_internos')->where('id', $bi->id)->update([
                'requisicao_id' => $bi->requisicao_id ?? $requisicao?->id,
                'laboratorio_id' => $bi->laboratorio_id ?? $laboratorio?->id,
                'numero' => $numero,
                'status' => $status,
                'recebido_area_em' => $bi->recebido_area_em ?? $bi->inicio_em,
            ]);

            $existePivot = DB::table('boletim_interno_amostras')
                ->where('boletim_interno_id', $bi->id)->where('amostra_id', $amostra->id)->exists();
            if (! $existePivot) {
                DB::table('boletim_interno_amostras')->insert([
                    'id' => (string) Str::uuid(),
                    'boletim_interno_id' => $bi->id,
                    'amostra_id' => $amostra->id,
                    'estado' => in_array($status, ['validado', 'emitido', 'devolvido', 'recebido_cgisc'], true) ? 'concluida' : 'em_ensaio',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('boletins_analises')->get() as $ba) {
            $bi = DB::table('boletins_internos')->where('id', $ba->boletim_interno_id)->first();
            if (! $bi) {
                continue;
            }

            $ordem = 0;
            foreach (['validador_1_id', 'validador_2_id', 'validador_3_id'] as $coluna) {
                $validadorId = $ba->{$coluna};
                if (! $validadorId) {
                    continue;
                }
                $ordem++;
                $existe = DB::table('validacoes')
                    ->where('boletim_interno_id', $bi->id)->where('validador_id', $validadorId)->exists();
                if (! $existe) {
                    DB::table('validacoes')->insert([
                        'id' => (string) Str::uuid(),
                        'boletim_interno_id' => $bi->id,
                        'validador_id' => $validadorId,
                        'ordem' => $ordem,
                        'tipo' => $ordem === 1 ? 'segundo' : ($ordem === 2 ? 'terceiro' : 'hierarquica'),
                        'assinatura' => null,
                        'observacao' => 'Migrado do modelo antigo (validador_'.$ordem.'_id).',
                        'created_at' => $ba->validado_em ?? $ba->updated_at ?? $now,
                    ]);
                }
            }

            $update = [
                'requisicao_id' => $ba->requisicao_id ?? $bi->requisicao_id,
                'laboratorio_id' => $ba->laboratorio_id ?? $bi->laboratorio_id,
            ];
            if ($bi->status === 'emitido' && ! $ba->numero) {
                $update['numero'] = 'BA-'.$bi->numero_analise;
                $update['emitido_em'] = $ba->comunicado_em ?? $ba->aprovado_em;
                $update['emitido_por_id'] = $ba->aprovado_por_id;
                $update['conteudo'] = $ba->resultado;
            }
            DB::table('boletins_analises')->where('id', $ba->id)->update($update);
        }

        foreach (DB::table('requisicoes')->get() as $requisicao) {
            $bis = DB::table('boletins_internos')->where('requisicao_id', $requisicao->id)->pluck('status');
            $amostrasStatus = DB::table('amostras')->where('requisicao_id', $requisicao->id)->pluck('status');

            if ($bis->isNotEmpty()) {
                $status = $bis->every(fn ($s) => $s === 'emitido') ? 'emitida' : 'em_processamento';
            } elseif ($amostrasStatus->isNotEmpty() && $amostrasStatus->every(fn ($s) => $s === 'rejeitada')) {
                $status = 'rejeitada';
            } else {
                $status = 'registada';
            }

            $update = ['status' => $requisicao->status && $requisicao->status !== 'registada' ? $requisicao->status : $status];
            if ($bis->isNotEmpty() && ! $requisicao->pat_numero && str_starts_with((string) $requisicao->numero, 'PAT-')
                && ! DB::table('requisicoes')->where('pat_numero', $requisicao->numero)->exists()) {
                $update['pat_numero'] = $requisicao->numero;
                $update['processo_aberto_em'] = $requisicao->created_at;
            }
            DB::table('requisicoes')->where('id', $requisicao->id)->update($update);
        }
    }

    public function down(): void
    {
        // Migração de dados: não reversível de forma segura (as tabelas novas
        // são removidas pelas migrations de estrutura correspondentes).
        DB::table('boletins_internos')->where('status', 'validado')->update(['status' => 'aprovado']);
        DB::table('boletins_internos')->where('status', 'emitido')->update(['status' => 'comunicado']);
    }
};
