<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NOTA: tabela `notifications` é um recurso de domínio próprio (contrato
 * `/api/notifications`, ver notification.ts), NÃO a tabela de notificações
 * genérica do Laravel (`Illuminate\Notifications\DatabaseNotification`,
 * schema diferente: type/notifiable_type/notifiable_id/data/read_at). Nunca
 * usar `$user->notify(...)` com canal 'database' nesta app — colidiria com
 * este schema.
 */
class Notification extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'title', 'message', 'type', 'link', 'read', 'created_at'];

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
