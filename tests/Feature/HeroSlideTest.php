<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\HeroSlide;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_index_publico_so_devolve_slides_publicados(): void
    {
        HeroSlide::factory()->count(2)->create(['published' => true]);
        HeroSlide::factory()->count(3)->create(['published' => false]);

        $response = $this->getJson('/api/hero-slides');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_admin_index_requer_autenticacao_e_devolve_todos_paginados(): void
    {
        $this->getJson('/api/hero-slides/admin')->assertUnauthorized();

        HeroSlide::factory()->count(2)->create(['published' => true]);
        HeroSlide::factory()->count(3)->create(['published' => false]);

        $admin = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $admin->id, 'role' => AppRole::Admin]);

        $this->actingAs($admin)->getJson('/api/hero-slides/admin')
            ->assertOk()
            ->assertJsonPath('meta.total', 5);
    }
}
