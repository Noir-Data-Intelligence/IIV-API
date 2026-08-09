<?php

namespace App\Enums;

/**
 * Chaves de modulo usadas pela matriz RBAC (`role_permissions.module`) e
 * pelo middleware `EnsureModulePermission`. Espelha `ALL_MODULES` em
 * Front-end/src/lib/permissions.ts. Novos modulos sao acrescentados aqui
 * a medida que cada Onda os introduz (ver SIG-IIV-MEMORIA-PROJETO.md
 * seccao 15) — nao e um enum porque a lista cresce ao longo do projecto.
 *
 * Excepção: 'observatorio' (Onda 11) ainda NÃO existe no frontend — a Onda
 * 11 não tem nenhum contrato REST a espelhar (sem endpoints, DTOs, páginas
 * ou mocks), ao contrário de todas as anteriores. Precisa de ser
 * acrescentado também a `Front-end/src/lib/permissions.ts` quando o
 * frontend do Observatório for construído.
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
    ];

    private function __construct() {}
}
