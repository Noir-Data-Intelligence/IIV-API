<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function notificationFor(User $user, array $overrides = []): Notification
    {
        return Notification::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Título',
            'message' => 'Mensagem',
            'type' => 'info',
            'link' => null,
            'read' => false,
            'created_at' => now(),
            ...$overrides,
        ]);
    }

    public function test_index_requer_autenticacao(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_index_so_devolve_notificacoes_do_utilizador_autenticado(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->notificationFor($me);
        $this->notificationFor($me);
        $this->notificationFor($other);

        $response = $this->actingAs($me)->getJson('/api/notifications');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_update_marca_uma_notificacao_como_lida(): void
    {
        $me = User::factory()->create();
        $notification = $this->notificationFor($me);

        $this->actingAs($me)->patchJson("/api/notifications/{$notification->id}", ['read' => true])
            ->assertOk()->assertJsonPath('read', true);
    }

    public function test_update_de_notificacao_de_outro_utilizador_devolve_404(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $notification = $this->notificationFor($other);

        $this->actingAs($me)->patchJson("/api/notifications/{$notification->id}", ['read' => true])
            ->assertNotFound();
    }

    public function test_mark_all_read_so_afecta_o_utilizador_autenticado(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->notificationFor($me);
        $this->notificationFor($me);
        $otherNotification = $this->notificationFor($other);

        $this->actingAs($me)->postJson('/api/notifications/mark-all-read')->assertNoContent();

        $this->assertSame(0, Notification::query()->where('user_id', $me->id)->where('read', false)->count());
        $this->assertFalse($otherNotification->fresh()->read);
    }

    public function test_clear_remove_apenas_as_notificacoes_do_utilizador_autenticado(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->notificationFor($me);
        $this->notificationFor($other);

        $this->actingAs($me)->deleteJson('/api/notifications')->assertNoContent();

        $this->assertSame(0, Notification::query()->where('user_id', $me->id)->count());
        $this->assertSame(1, Notification::query()->where('user_id', $other->id)->count());
    }
}
