<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_root_redirects_member_to_stocks(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/')
            ->assertRedirect(route('stocks.index'));
    }

    public function test_root_redirects_admin_to_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin.users'));
    }

    public function test_authenticated_user_is_redirected_away_from_guest_pages(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/login')->assertRedirect();
        $this->actingAs($member)->get('/register')->assertRedirect();
    }

    public function test_stock_routes_render_for_member(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('stocks.index'))
            ->assertOk()
            ->assertSee('Daftar Stok Barang Saya');
    }

    public function test_user_with_unknown_role_is_forbidden_from_stock_routes(): void
    {
        $user = User::factory()->create(['role' => 'guest']);

        $this->actingAs($user)
            ->get(route('stocks.index'))
            ->assertStatus(403);
    }

    public function test_health_check_endpoint(): void
    {
        $this->get('/up')
            ->assertStatus(200);
    }
}
