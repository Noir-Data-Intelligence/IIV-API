<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_publico_cria_mensagem(): void
    {
        $response = $this->postJson('/api/contact-messages', [
            'nome' => 'João Sachiwo',
            'email' => 'joao@example.com',
            'assunto' => 'Dúvida sobre serviços',
            'mensagem' => 'Gostaria de saber mais sobre os serviços laboratoriais.',
        ]);

        $response->assertCreated()->assertJsonStructure(['id']);
        $this->assertDatabaseHas('contact_messages', ['email' => 'joao@example.com']);
    }

    public function test_store_com_campos_em_falta_devolve_422(): void
    {
        $this->postJson('/api/contact-messages', ['nome' => 'X'])->assertStatus(422);
    }

    public function test_index_requer_autenticacao_e_permissao_mensagens(): void
    {
        $this->getJson('/api/contact-messages')->assertUnauthorized();

        $tecnico = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $tecnico->id, 'role' => AppRole::Tecnico]);
        $this->actingAs($tecnico)->getJson('/api/contact-messages')->assertForbidden();
    }

    public function test_update_marca_como_lida(): void
    {
        $message = ContactMessage::query()->create([
            'id' => (string) Str::uuid(), 'nome' => 'X', 'email' => 'x@x.com',
            'assunto' => 'Y', 'mensagem' => 'Z', 'created_at' => now(),
        ]);

        $admin = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $admin->id, 'role' => AppRole::Admin]);

        $this->actingAs($admin)->patchJson("/api/contact-messages/{$message->id}", ['lida' => true])
            ->assertOk()->assertJsonPath('lida', true);
    }
}
