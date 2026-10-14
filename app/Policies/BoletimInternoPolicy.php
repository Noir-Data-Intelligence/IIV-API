<?php

namespace App\Policies;

use App\Enums\AppRole;
use App\Models\BoletimInterno;
use App\Models\Laboratorio;
use App\Models\User;
use App\Services\Lab\LabSupport;
use App\Services\PermissionMatrixService;

/**
 * Escopo por área (DEF-002): o RBAC por módulo (`lab-area`) diz O QUE um
 * técnico pode fazer; esta policy diz EM QUE área — só na(s) área(s) a que
 * pertence (`laboratorio_tecnicos`). Director de Laboratório, Responsável de
 * Qualidade e admin passam sempre (supervisão).
 */
class BoletimInternoPolicy
{
    public function __construct(
        private readonly LabSupport $support,
        private readonly PermissionMatrixService $permissions,
    ) {}

    /** Trabalhar no BI (receber, ensaios, rejeitar na área, devolver, reencaminhar). */
    public function operarNaArea(User $user, BoletimInterno $bi): bool
    {
        return $bi->laboratorio !== null && $this->support->podeOperarNaArea($user, $bi->laboratorio);
    }

    /** Ver o BI: CGISC (recepção/emissão) e validação vêem todos; técnicos só os da sua área. */
    public function view(User $user, BoletimInterno $bi): bool
    {
        if ($this->veTodasAsAreas($user)) {
            return true;
        }

        return $this->operarNaArea($user, $bi);
    }

    /** Ver a fila de uma área. */
    public function verFilaArea(User $user, Laboratorio $laboratorio): bool
    {
        return $user->hasRole(AppRole::Diretor) || $this->support->podeOperarNaArea($user, $laboratorio);
    }

    private function veTodasAsAreas(User $user): bool
    {
        $roles = $user->roleValues();
        foreach (['lab-recepcao', 'lab-emissao', 'lab-validacao'] as $modulo) {
            if ($this->permissions->canViewAny($roles, $modulo)) {
                return true;
            }
        }

        return $this->support->supervisiona($user);
    }
}
