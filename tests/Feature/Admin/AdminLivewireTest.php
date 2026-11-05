<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $targetUser = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/admin/users')
            ->assertStatus(403);

        $this->actingAs($member)
            ->get("/admin/users/{$targetUser->id}/stocks")
            ->assertStatus(403);
    }

    public function test_admin_pages_render_over_http(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Rina Wati']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Rina Wati');

        $this->actingAs($admin)
            ->get("/admin/users/{$member->id}/stocks")
            ->assertOk()
            ->assertSee('Stok Barang Pengguna: Rina Wati')
            ->assertSee('Pengguna ini belum memiliki stok barang.');
    }

    public function test_admin_can_view_user_list_with_stock_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@test.com']);

        Stock::factory()->create(['user_id' => $budi->id, 'quantity' => 30]);
        Stock::factory()->lowStock()->create(['user_id' => $budi->id]);
        Stock::factory()->outOfStock()->create(['user_id' => $budi->id]);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->assertSee('Budi Santoso')
            ->assertSee('Citra Lestari')
            ->assertViewHas('users', function ($users) use ($budi) {
                $row = $users->firstWhere('id', $budi->id);

                return (int) $row->stocks_count === 3
                    && (int) $row->stocks_sum_quantity === 33
                    && (int) $row->low_stock_count === 1
                    && (int) $row->out_of_stock_count === 1;
            })
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Citra Lestari');
    }

    public function test_admin_can_view_user_stocks_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Dewi Sartika']);

        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Pensil 2B', 'status' => 'available']);
        Stock::factory()->outOfStock()->create(['user_id' => $member->id, 'name' => 'Penghapus Karet']);

        $this->actingAs($admin);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Dewi Sartika')
            ->assertSee('Mode Lihat Saja (Read-Only Admin)')
            ->assertSee('Pensil 2B')
            ->assertSee('Penghapus Karet')
            ->assertViewHas('summary', fn (array $summary) => $summary['total_items'] === 2 && $summary['out_of_stock'] === 1)
            ->set('search', 'Pensil')
            ->assertSee('Pensil 2B')
            ->assertDontSee('Penghapus Karet')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Penghapus Karet')
            ->assertDontSee('Pensil 2B');
    }

    public function test_admin_can_export_user_stocks(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Eko Prasetyo']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Stok Export Admin']);

        $this->actingAs($admin);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
