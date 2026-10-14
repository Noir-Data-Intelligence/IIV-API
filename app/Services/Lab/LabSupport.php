<?php

namespace App\Services\Lab;

use App\Enums\AmostraStatus;
use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Models\Amostra;
use App\Models\AmostraMovimento;
use App\Models\BoletimInterno;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Peças partilhadas pelos serviços de fase (Recepção, Encaminhamento, Área,
 * Emissão): transições guardadas, rastreabilidade, escopo por área e
 * sincronização do estado do processo (requisição) a partir dos BI.
 */
class LabSupport
{
    /** Local lógico "CGISC" (recepção) usado nos movimentos. */
    public const CGISC = 'CGISC';

    /** Papéis que operam em qualquer área sem pertencer à equipa (supervisão). */
    public const PAPEIS_SUPERVISAO = [AppRole::Admin, AppRole::DirectorLaboratorio, AppRole::ResponsavelQualidade];

    public function localArea(?Laboratorio $laboratorio): string
    {
        return $laboratorio ? "Área {$laboratorio->code}" : 'Área';
    }

    public function supervisiona(User $user): bool
    {
        foreach (self::PAPEIS_SUPERVISAO as $papel) {
            if ($user->hasRole($papel)) {
                return true;
            }
        }

        return false;
    }

    /** Membro da equipa da área (`laboratorio_tecnicos`) ou papel de supervisão. */
    public function podeOperarNaArea(User $user, Laboratorio $laboratorio): bool
    {
        return $this->supervisiona($user) || $laboratorio->temMembro($user);
    }

    /** @throws AuthorizationException (403) */
    public function exigirMembroDaArea(User $user, BoletimInterno $bi): void
    {
        $laboratorio = $bi->laboratorio;
        if (! $laboratorio || ! $this->podeOperarNaArea($user, $laboratorio)) {
            throw new AuthorizationException(
                'Só a equipa da área '.($laboratorio->code ?? '?').' (ou a Direcção/Qualidade) pode operar este Boletim Interno.'
            );
        }
    }

    /**
     * Aplica uma transição do BI — recusa (422) qualquer transição que a
     * máquina de estados não permita. Única forma de mudar `status`.
     */
    public function transitar(BoletimInterno $bi, BoletimInternoStatus $alvo, array $extra = []): void
    {
        $actual = $bi->status;
        if (! $actual->canTransitionTo($alvo)) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida do Boletim Interno {$bi->numero}: {$actual->value} → {$alvo->value}."],
            ]);
        }

        $bi->update(['status' => $alvo, ...$extra]);
    }

    /** @param  list<BoletimInternoStatus>  $permitidos */
    public function exigirEstado(BoletimInterno $bi, array $permitidos, string $accao): void
    {
        if (! in_array($bi->status, $permitidos, true)) {
            $lista = implode(', ', array_map(fn ($s) => $s->value, $permitidos));
            throw ValidationException::withMessages([
                'status' => ["Não é possível {$accao}: o Boletim Interno {$bi->numero} está em \"{$bi->status->value}\" (exige: {$lista})."],
            ]);
        }
    }

    public function transitarAmostra(Amostra $amostra, AmostraStatus $alvo, array $extra = []): void
    {
        if ($amostra->status === $alvo) {
            if ($extra) {
                $amostra->update($extra);
            }

            return;
        }
        if (! $amostra->status->canTransitionTo($alvo)) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida da amostra {$amostra->numero}: {$amostra->status->value} → {$alvo->value}."],
            ]);
        }
        $amostra->update(['status' => $alvo, ...$extra]);
    }

    public function movimento(
        Amostra|string $amostra,
        string $accao,
        ?User $user,
        ?BoletimInterno $bi = null,
        ?string $de = null,
        ?string $para = null,
        ?string $observacao = null,
    ): AmostraMovimento {
        return AmostraMovimento::query()->create([
            'id' => (string) Str::uuid(),
            'amostra_id' => $amostra instanceof Amostra ? $amostra->id : $amostra,
            'boletim_interno_id' => $bi?->id,
            'accao' => $accao,
            'de_local' => $de,
            'para_local' => $para,
            'user_id' => $user?->id,
            'observacao' => $observacao,
        ]);
    }

    /** Regista o mesmo movimento para todas as amostras activas do BI. */
    public function movimentoBi(BoletimInterno $bi, string $accao, ?User $user, ?string $de = null, ?string $para = null, ?string $observacao = null): void
    {
        foreach ($bi->amostras()->get() as $amostra) {
            $this->movimento($amostra, $accao, $user, $bi, $de, $para, $observacao);
        }
    }

    /**
     * Do processo aberto em diante, o estado da requisição é derivado dos
     * seus BI: todos terminais -> emitida; todos na CGISC/terminais ->
     * aguarda_emissao; algum já recebido numa área -> em_processamento.
     */
    public function sincronizarRequisicao(Requisicao $requisicao): void
    {
        $requisicao->refresh();
        $estado = $requisicao->status;

        if (in_array($estado, [RequisicaoStatus::Registada, RequisicaoStatus::EmTriagem, RequisicaoStatus::Rejeitada, RequisicaoStatus::Encerrada], true)) {
            return;
        }

        $estados = $requisicao->boletinsInternos()->pluck('status');
        if ($estados->isEmpty()) {
            return;
        }

        $naCgiscOuTerminal = [BoletimInternoStatus::RecebidoCgisc, BoletimInternoStatus::Emitido, BoletimInternoStatus::RejeitadoNaArea];

        if ($estados->every(fn (BoletimInternoStatus $s) => $s->isTerminal())) {
            $novo = RequisicaoStatus::Emitida;
        } elseif ($estados->every(fn (BoletimInternoStatus $s) => in_array($s, $naCgiscOuTerminal, true))) {
            $novo = RequisicaoStatus::AguardaEmissao;
        } elseif ($estados->contains(fn (BoletimInternoStatus $s) => ! in_array($s, [BoletimInternoStatus::Gerado, BoletimInternoStatus::EmTransito], true))) {
            $novo = RequisicaoStatus::EmProcessamento;
        } else {
            $novo = RequisicaoStatus::ProcessoAberto;
        }

        if ($novo !== $estado) {
            $requisicao->update(['status' => $novo]);
        }
    }

    /** Amostras cujos BI estão todos terminais passam a `concluida`. */
    public function concluirAmostrasSeTerminadas(BoletimInterno $bi): void
    {
        foreach ($bi->amostras()->get() as $amostra) {
            if (! $amostra->status->foiAceite() || $amostra->status === AmostraStatus::Concluida) {
                continue;
            }
            $todosTerminais = $amostra->boletinsInternos()->get()->every(fn (BoletimInterno $b) => $b->status->isTerminal());
            if ($todosTerminais) {
                $this->transitarAmostra($amostra, AmostraStatus::Concluida);
            }
        }
    }
}
