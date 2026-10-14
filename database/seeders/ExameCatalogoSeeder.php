<?php

namespace Database\Seeders;

use App\Models\ExameCatalogo;
use App\Models\Laboratorio;
use Illuminate\Database\Seeder;

/**
 * Catálogo de exames — DADOS DE DEMONSTRAÇÃO (1-2 exames plausíveis por cada
 * uma das 9 áreas), para que o fluxo "área derivada dos exames pedidos"
 * (DEF-001) funcione de ponta a ponta. A lista real de exames, POPs,
 * técnicas, tipos de amostra e SLAs tem de ser confirmada com o laboratório
 * e mantida no ecrã "Catálogo de Exames" (módulo `lab-exames`).
 *
 * Idempotente (updateOrCreate por `codigo`). Degrada graciosamente se uma
 * área não existir (corre depois do LaboratorioSeeder).
 */
class ExameCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $exames = [
            // [área, código, nome, técnica, POP, tipos de amostra, SLA (h), campos de resultado]
            ['AP', 'AP-COPRO', 'Coprologia quantitativa (McMaster)', 'McMaster', null, ['fezes'], 72,
                [['chave' => 'opg', 'tipo' => 'numero', 'rotulo' => 'Ovos por grama', 'unidade' => 'OPG']]],
            ['AP', 'AP-HEMO', 'Pesquisa de hemoparasitas (esfregaço)', 'Esfregaço corado (Giemsa)', null, ['sangue'], 48,
                [['chave' => 'hemoparasitas', 'tipo' => 'opcao', 'opcoes' => ['ausente', 'presente']], ['chave' => 'especie', 'tipo' => 'texto', 'obrigatorio' => false]]],
            ['AB', 'AB-CULT', 'Cultura e isolamento bacteriano', 'Cultura em meios selectivos', null, ['zaragatoa', 'tecido', 'leite', 'urina', 'outro'], 72,
                [['chave' => 'crescimento', 'tipo' => 'opcao', 'opcoes' => ['sem_crescimento', 'escasso', 'moderado', 'abundante']], ['chave' => 'agente_isolado', 'tipo' => 'texto', 'obrigatorio' => false]]],
            ['AB', 'AB-TSA', 'Teste de sensibilidade a antimicrobianos (TSA)', 'Difusão em disco (Kirby-Bauer)', null, ['zaragatoa', 'tecido', 'leite', 'urina', 'outro'], 96,
                [['chave' => 'perfil', 'tipo' => 'texto']]],
            ['AS', 'AS-BRUC-RB', 'Brucelose — Rosa Bengala', 'Aglutinação (Rosa Bengala)', null, ['soro'], 24,
                [['chave' => 'reaccao', 'tipo' => 'opcao', 'opcoes' => ['negativo', 'positivo']]]],
            ['AS', 'AS-ELISA', 'ELISA indirecto', 'ELISA', null, ['soro'], 72,
                [['chave' => 'densidade_optica', 'tipo' => 'numero'], ['chave' => 'interpretacao', 'tipo' => 'opcao', 'opcoes' => ['negativo', 'duvidoso', 'positivo']]]],
            ['BM', 'BM-PCR', 'PCR em tempo real', 'qPCR', null, ['sangue', 'tecido', 'zaragatoa'], 24,
                [['chave' => 'alvo_detectado', 'tipo' => 'booleano'], ['chave' => 'ct', 'tipo' => 'numero', 'obrigatorio' => false]]],
            ['BR', 'BR-SALM', 'Pesquisa de Salmonella spp.', 'Pré-enriquecimento + meios selectivos', null, ['leite', 'outro'], 120,
                [['chave' => 'salmonela', 'tipo' => 'numero', 'rotulo' => 'Salmonella spp. (ufc/25 g)']]],
            ['BR', 'BR-COMP', 'Composição centesimal', 'Gravimetria / Kjeldahl', null, ['leite', 'outro'], 120,
                [['chave' => 'humidade', 'tipo' => 'numero', 'unidade' => '%'], ['chave' => 'proteina', 'tipo' => 'numero', 'unidade' => '%']]],
            ['AMA', 'AMA-COLI', 'Contagem de coliformes', 'Contagem em placa', null, ['agua', 'leite', 'outro'], 120,
                [['chave' => 'ufc_ml', 'tipo' => 'numero', 'unidade' => 'ufc/ml']]],
            ['AR', 'AR-IFD', 'Raiva — Imunofluorescência directa', 'IFD', null, ['tecido'], 48,
                [['chave' => 'resultado', 'tipo' => 'opcao', 'opcoes' => ['negativo', 'positivo']]]],
            ['AMC', 'AMC-ESTER', 'Controlo de esterilização de meios de cultura', 'Incubação de controlo', null, ['outro'], 48,
                [['chave' => 'esteril', 'tipo' => 'booleano']]],
            ['INC', 'INC-CTRL', 'Incubação controlada', 'Estufa calibrada', null, ['outro'], 72,
                [['chave' => 'temperatura', 'tipo' => 'numero', 'unidade' => '°C']]],
        ];

        $laboratorios = Laboratorio::query()->get()->keyBy('code');

        foreach ($exames as [$code, $codigo, $nome, $tecnica, $pop, $tipos, $sla, $campos]) {
            $laboratorio = $laboratorios->get($code);
            if (! $laboratorio) {
                continue;
            }

            ExameCatalogo::query()->updateOrCreate(
                ['codigo' => $codigo],
                [
                    'laboratorio_id' => $laboratorio->id,
                    'nome' => $nome,
                    'tecnica_metodo' => $tecnica,
                    'pop_codigo' => $pop, // POP real a confirmar com o laboratório
                    'tipos_amostra' => $tipos,
                    'sla_horas' => $sla,
                    'campos_resultado' => $campos,
                    'activo' => true,
                ],
            );
        }
    }
}
