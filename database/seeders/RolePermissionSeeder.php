<?php

namespace Database\Seeders;

use App\Enums\AppRole;
use App\Enums\ModuleKey;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

/**
 * Copia EXACTA de `ROLE_PERMISSIONS` em Front-end/src/lib/permissions.ts —
 * qualquer alteração ali tem de ser replicada aqui (ver
 * SIG-IIV-MEMORIA-PROJETO.md secção 9/15: distribuição razoável a partir do
 * modelo de 5 papéis, AINDA NÃO validada célula-a-célula contra a matriz
 * V/C/E/Va/A da REQ-005 §13 — afinar durante esta Onda quando essa matriz
 * for transcrita).
 *
 * "admin" fica de fora deliberadamente: PermissionMatrixService::forRole()
 * dá-lhe sempre acesso total sem depender da tabela (mesmo safety net do
 * canView()/canWrite() no frontend).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            AppRole::Diretor->value => [
                'view' => ModuleKey::ALL,
                'write' => [],
            ],
            AppRole::DirectorLaboratorio->value => [
                'view' => [
                    'painel', 'perfil', 'laboratorios', 'analises', 'resultados', 'insumos',
                    'animais', 'inseminacao', 'auditorias', 'nao-conformidades', 'logs', 'acessibilidade',
                    'historico-alertas', 'documentos', 'processos', 'missoes', 'formacoes', 'investigacao',
                    'avaliacoes', 'bi', 'observatorio',
                    // Pedido do cliente: gestão de equipas de Técnicos + nomeação de
                    // validadores por laboratório — precisa de ver (não escrever) a
                    // lista de utilizadores para os escolher. Sem escopo por
                    // laboratório no RBAC actual (é por módulo global); aceite
                    // deliberadamente — a UI filtra visualmente para o relevante.
                    'utilizadores',
                ],
                'write' => ['laboratorios', 'analises', 'resultados', 'insumos', 'auditorias', 'nao-conformidades', 'documentos', 'processos'],
            ],
            AppRole::ResponsavelQualidade->value => [
                'view' => [
                    'painel', 'perfil', 'laboratorios', 'analises', 'resultados',
                    'auditorias', 'nao-conformidades', 'logs', 'acessibilidade',
                    'historico-alertas', 'documentos', 'processos',
                ],
                'write' => ['auditorias', 'nao-conformidades', 'logs', 'acessibilidade', 'documentos', 'processos'],
            ],
            AppRole::Tecnico->value => [
                'view' => [
                    'painel', 'perfil', 'laboratorios', 'analises', 'resultados', 'insumos',
                    'animais', 'inseminacao', 'historico-alertas', 'documentos', 'processos',
                    'stock', 'agricultura', 'pecuaria', 'patrimonio', 'missoes', 'formacoes',
                    'investigacao', 'avaliacoes', 'observatorio',
                ],
                'write' => [
                    'analises', 'resultados', 'insumos', 'animais', 'inseminacao', 'documentos', 'processos',
                    'stock', 'agricultura', 'pecuaria', 'missoes', 'formacoes', 'investigacao',
                ],
            ],
            AppRole::Recepcionista->value => [
                'view' => ['painel', 'perfil', 'analises', 'distribuicao', 'historico-alertas', 'documentos', 'processos', 'missoes', 'formacoes', 'investigacao', 'avaliacoes'],
                'write' => ['processos'],
            ],
            AppRole::GestorStock->value => [
                'view' => ['painel', 'perfil', 'insumos', 'stock', 'agricultura', 'pecuaria', 'produtos', 'lotes', 'planeamento', 'distribuicao', 'historico-alertas', 'documentos', 'processos'],
                'write' => ['insumos', 'stock', 'agricultura', 'pecuaria', 'produtos', 'lotes', 'planeamento', 'distribuicao', 'documentos', 'processos'],
            ],
            AppRole::GestorPatrimonio->value => [
                'view' => ['painel', 'perfil', 'patrimonio', 'estacoes', 'historico-alertas', 'documentos', 'processos'],
                'write' => ['patrimonio', 'documentos', 'processos'],
            ],
            AppRole::GestorFinanceiro->value => [
                'view' => ['painel', 'perfil', 'financeiro', 'bi', 'historico-alertas', 'documentos', 'processos'],
                'write' => ['financeiro', 'documentos', 'processos'],
            ],
            AppRole::GestorRh->value => [
                'view' => ['painel', 'perfil', 'rh', 'formacoes', 'missoes', 'historico-alertas', 'documentos', 'processos'],
                'write' => ['rh', 'formacoes', 'missoes', 'documentos', 'processos'],
            ],
            AppRole::GestorEstacao->value => [
                'view' => ['painel', 'perfil', 'estacoes', 'animais', 'inseminacao', 'agricultura', 'pecuaria', 'historico-alertas', 'documentos', 'processos', 'observatorio'],
                'write' => ['estacoes', 'animais', 'inseminacao', 'agricultura', 'pecuaria', 'documentos', 'processos'],
            ],
            AppRole::Isv->value => [
                // RF-072–075: o Observatório disponibiliza ao ISV a informação
                // territorial consolidada (RDA §8.2 "Apoio à decisão do ISV").
                'view' => ['painel', 'processos', 'historico-alertas', 'observatorio'],
                'write' => [],
            ],
        ];

        foreach ($matrix as $role => $perms) {
            foreach (ModuleKey::ALL as $module) {
                // 'id' fica de fora do payload — o Model (HasUuids) gera-o
                // sozinho na criação; incluí-lo aqui faria o updateOrCreate
                // sobrescrever a PK em cada re-seed.
                RolePermission::query()->updateOrCreate(
                    ['role' => $role, 'module' => $module],
                    [
                        'can_view' => in_array($module, $perms['view'], true),
                        'can_write' => in_array($module, $perms['write'], true),
                    ],
                );
            }
        }
    }
}
