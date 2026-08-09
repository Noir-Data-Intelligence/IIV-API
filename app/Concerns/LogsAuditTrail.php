<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Trilho de auditoria transversal: regista created/updated/deleted com
 * actor/timestamp/antes-depois. Usar em qualquer Model que precise de
 * auditoria (a maioria dos dominios de negocio, a partir da Onda 3) — basta
 * `use LogsAuditTrail;` e chamar `static::bootLogsAuditTrail()` e tratado
 * automaticamente pelo Eloquent.
 *
 * Para transicoes de workflow nomeadas (ex: "avancar", "aprovar"), chamar
 * `logAuditEvent($model, 'avancar', $oldValues, $newValues)` explicitamente
 * a partir do Workflow Service — o boot() so cobre CRUD simples.
 */
trait LogsAuditTrail
{
    public static function bootLogsAuditTrail(): void
    {
        static::created(function (Model $model) {
            static::recordAuditEvent($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            static::recordAuditEvent($model, 'updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function (Model $model) {
            static::recordAuditEvent($model, 'deleted', $model->getOriginal(), null);
        });
    }

    protected static function recordAuditEvent(Model $model, string $action, ?array $old, ?array $new): void
    {
        // Cópia desnormalizada do nome/email do actor: `actor_id` é
        // `nullOnDelete()`, sem isto a autoria desaparecia do trilho de
        // auditoria assim que a conta fosse apagada (auditoria de
        // segurança, 2026-08-09).
        $actor = Auth::user();

        AuditLog::query()->create([
            'id' => (string) Str::uuid(),
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->full_name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
