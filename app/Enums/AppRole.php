<?php

namespace App\Enums;

/**
 * Os 12 papeis institucionais da REQ-005 SS13 — espelha exactamente
 * `AppRole` em Front-end/src/lib/permissions.ts. Qualquer alteracao aqui
 * tem de ser replicada la (e vice-versa); os valores (strings) sao o
 * contrato, usados tal e qual em `roles`, `role_permissions.role`, etc.
 */
enum AppRole: string
{
    case Admin = 'admin';
    case Diretor = 'diretor';
    case DirectorLaboratorio = 'director-laboratorio';
    case ResponsavelQualidade = 'responsavel-qualidade';
    case Tecnico = 'tecnico';
    case Recepcionista = 'recepcionista';
    case GestorStock = 'gestor-stock';
    case GestorPatrimonio = 'gestor-patrimonio';
    case GestorFinanceiro = 'gestor-financeiro';
    case GestorRh = 'gestor-rh';
    case GestorEstacao = 'gestor-estacao';
    case Isv = 'isv';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * Papel principal de um conjunto de papeis: o primeiro por ordem
     * alfabetica — mesma regra de desempate de `primaryRole()` no frontend
     * (src/lib/permissions.ts), para que a autorizacao do servidor nunca
     * divirja da UX do cliente quando um utilizador tem varios papeis.
     *
     * @param  list<self>  $roles
     */
    public static function primary(array $roles): ?self
    {
        if ($roles === []) {
            return null;
        }

        $sorted = $roles;
        usort($sorted, fn (self $a, self $b) => $a->value <=> $b->value);

        return $sorted[0];
    }
}
