<?php

namespace App\Services\Lab;

use Illuminate\Support\Facades\DB;

/**
 * Numeração sequencial anual sem duplicados: tabela `sequencias(chave, ano,
 * ultimo)` incrementada sob `lockForUpdate()` dentro de uma transacção
 * (mesmo princípio do LoteController::gerarNumeroLote — max + lock — mas com
 * uma linha por série, para que o lock seja curto e não dependa de varrer a
 * tabela de negócio). Substitui o antigo `count()+1`, que gerava PATs
 * duplicados entre a série da requisição e a do BI e colidia em
 * concorrência.
 *
 * Na primeira utilização de uma série/ano, a linha é semeada com o maior
 * número já existente nos dados (ex.: PATs criados pelo modelo antigo), para
 * nunca reutilizar um número.
 */
class NumeracaoService
{
    public function proximo(string $chave, ?int $ano = null, ?callable $semente = null): int
    {
        $ano ??= (int) now()->year;

        return DB::transaction(function () use ($chave, $ano, $semente) {
            $linha = DB::table('sequencias')->where('chave', $chave)->where('ano', $ano)->lockForUpdate()->first();

            if (! $linha) {
                DB::table('sequencias')->insertOrIgnore([
                    'chave' => $chave,
                    'ano' => $ano,
                    'ultimo' => $semente ? (int) $semente($ano) : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $linha = DB::table('sequencias')->where('chave', $chave)->where('ano', $ano)->lockForUpdate()->first();
            }

            $proximo = ((int) $linha->ultimo) + 1;
            DB::table('sequencias')->where('id', $linha->id)->update(['ultimo' => $proximo, 'updated_at' => now()]);

            return $proximo;
        });
    }

    /** PAT do processo: `PAT-###-ANO` (1 por requisição aceite — D.0#3, a confirmar). */
    public function pat(): string
    {
        $ano = (int) now()->year;
        $n = $this->proximo('PAT', $ano, fn (int $ano) => max(
            $this->maiorPat('requisicoes', 'pat_numero', $ano),
            $this->maiorPat('requisicoes', 'numero', $ano),
            $this->maiorPat('boletins_internos', 'numero_analise', $ano),
        ));

        return sprintf('PAT-%03d-%d', $n, $ano);
    }

    /** Número de registo da requisição na recepção: `REQ-ANO-####`. */
    public function requisicao(): string
    {
        $ano = (int) now()->year;

        return sprintf('REQ-%d-%04d', $ano, $this->proximo('REQ', $ano, fn (int $ano) => $this->maiorSufixo('requisicoes', 'numero', "REQ-{$ano}-")));
    }

    /** Número do Boletim de Análises oficial: `BA-ANO-####`. */
    public function boletimAnalises(): string
    {
        $ano = (int) now()->year;

        return sprintf('BA-%d-%04d', $ano, $this->proximo('BA', $ano, fn (int $ano) => $this->maiorSufixo('boletins_analises', 'numero', "BA-{$ano}-")));
    }

    /** Número do Termo de Entrega (amostras de proveniência não local): `TE-ANO-####`. */
    public function termoEntrega(): string
    {
        $ano = (int) now()->year;

        return sprintf('TE-%d-%04d', $ano, $this->proximo('TE', $ano, fn (int $ano) => $this->maiorSufixo('requisicoes', 'termo_entrega_numero', "TE-{$ano}-")));
    }

    private function maiorPat(string $tabela, string $coluna, int $ano): int
    {
        $maior = 0;
        foreach (DB::table($tabela)->where($coluna, 'like', "PAT-%-{$ano}%")->pluck($coluna) as $valor) {
            if (preg_match('/^PAT-(\d+)-'.$ano.'/', (string) $valor, $m)) {
                $maior = max($maior, (int) $m[1]);
            }
        }

        return $maior;
    }

    private function maiorSufixo(string $tabela, string $coluna, string $prefixo): int
    {
        $maior = 0;
        foreach (DB::table($tabela)->where($coluna, 'like', $prefixo.'%')->pluck($coluna) as $valor) {
            $maior = max($maior, (int) substr((string) $valor, strlen($prefixo)));
        }

        return $maior;
    }
}
