<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new StockRepository());
    }

    public function test_get_users_list_without_search(): void
    {
        User::factory()->count(3)->create();

        $this->assertCount(3, $this->service->getUsersList());
    }

    public function test_get_users_list_with_search(): void
    {
        User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@test.com']);
        User::factory()->create(['name' => 'Bob Marley', 'email' => 'bob@test.com']);
        User::factory()->create(['name' => 'Charlie Chaplin', 'username' => 'charlie_legend', 'email' => 'charlie@test.com']);

        $this->assertCount(1, $this->service->getUsersList(search: 'Alice'));
        $this->assertCount(1, $this->service->getUsersList(search: 'bob@test.com'));
        $this->assertCount(1, $this->service->getUsersList(search: 'charlie_legend'));
    }

    public function test_get_users_list_includes_stock_summary(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 10, 'status' => 'available']);
        Stock::factory()->lowStock()->create(['user_id' => $user->id]);
        Stock::factory()->outOfStock()->count(2)->create(['user_id' => $user->id]);

        $empty = User::factory()->create();

        $list = $this->service->getUsersList();
        $row = $list->firstWhere('id', $user->id);

        $this->assertSame(4, (int) $row->stocks_count);
        $this->assertSame(13, (int) $row->stocks_sum_quantity);
        $this->assertSame(1, (int) $row->low_stock_count);
        $this->assertSame(2, (int) $row->out_of_stock_count);

        $emptyRow = $list->firstWhere('id', $empty->id);
        $this->assertSame(0, (int) $emptyRow->stocks_count);
        $this->assertNull($emptyRow->stocks_sum_quantity);
    }

    public function test_get_user_stocks_and_summary(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(4)->create(['user_id' => $user->id, 'quantity' => 10]);
        Stock::factory()->count(2)->create();

        $this->assertCount(4, $this->service->getUserStocks($user->id));
        $this->assertSame(4, $this->service->getUserStockSummary($user->id)['total_items']);
        $this->assertSame(40, $this->service->getUserStockSummary($user->id)['total_quantity']);
    }
}
