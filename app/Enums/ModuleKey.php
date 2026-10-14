<?php

namespace App\Enums;

/**
 * Chaves de modulo usadas pela matriz RBAC (`role_permissions.module`) e
 * pelo middleware `EnsureModulePermission`. Espelha `ALL_MODULES` em
 * Front-end/src/lib/permissions.ts. Novos modulos sao acrescentados aqui
 * a medida que cada Onda os introduz (ver SIG-IIV-MEMORIA-PROJETO.md
 * seccao 15) — nao e um enum porque a lista cresce ao longo do projecto.
 */
final class ModuleKey
{
    /** @var list<string> */
    public const ALL = [
        'painel', 'perfil', 'utilizadores', 'departamentos',
        'laboratorios', 'analises', 'resultados', 'insumos',
        'produtos', 'lotes', 'planeamento', 'distribuicao',
        'estacoes', 'auditorias', 'nao-conformidades', 'logs',
        'acessibilidade', 'legislacao', 'noticias', 'mensagens', 'slideshow', 'rbac',
        'historico-alertas', 'documentos', 'processos', 'animais', 'inseminacao',
        'stock', 'agricultura', 'pecuaria', 'financeiro', 'patrimonio',
        'missoes', 'rh', 'formacoes', 'investigacao', 'avaliacoes', 'bi',
        'observatorio',
        // Laboratório por fase (redesenho DEF-001/002/003). 'analises' e
        // 'resultados' ficam só para as rotas de leitura legadas.
        'lab-recepcao', 'lab-area', 'lab-validacao', 'lab-emissao', 'lab-exames',
    ];

    /** @var list<string> */
    public const LAB = ['lab-recepcao', 'lab-area', 'lab-validacao', 'lab-emissao', 'lab-exames'];

    private function __construct() {}
}
