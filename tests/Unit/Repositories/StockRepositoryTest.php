<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected StockRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new StockRepository();
    }

    public function test_create_and_find(): void
    {
        $user = User::factory()->create();
        $stock = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Test Stock',
            'quantity' => 10,
        ]);

        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Test Stock']);

        $this->assertEquals($stock->id, $this->repository->find($stock->id)?->id);
        $this->assertNull($this->repository->find(9999));
        $this->assertEquals($stock->id, $this->repository->findOrFail($stock->id)->id);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(9999);
    }

    public function test_update_and_delete(): void
    {
        $user = User::factory()->create();
        $stock = $this->repository->create(['user_id' => $user->id, 'name' => 'Original']);

        $this->assertTrue($this->repository->update($stock, ['name' => 'Updated Name']));
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Updated Name']);

        $this->assertTrue($this->repository->delete($stock));
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_all_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Stock::factory()->count(3)->create(['user_id' => $user->id]);
        Stock::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $this->assertCount(3, $this->repository->allForUser($user->id));
    }

    public function test_for_user_with_filters(): void
    {
        $user = User::factory()->create();

        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Laptop Asus', 'sku' => 'ELK-001', 'status' => 'available']);
        Stock::factory()->outOfStock()->create(['user_id' => $user->id, 'name' => 'Mouse Logitech', 'sku' => 'ELK-002']);
        Stock::factory()->lowStock()->create(['user_id' => $user->id, 'name' => 'Kabel', 'sku' => 'NET-001', 'description' => 'Untuk laptop kantor']);

        $this->assertCount(3, $this->repository->forUser($user->id));
        $this->assertCount(1, $this->repository->forUser($user->id, status: 'out_of_stock'));
        $this->assertCount(2, $this->repository->forUser($user->id, search: 'ELK')); // SKU
        $this->assertCount(2, $this->repository->forUser($user->id, search: 'laptop')); // name + description
        $this->assertCount(1, $this->repository->forUser($user->id, perPage: 1));
    }

    public function test_summary_for_user(): void
    {
        $user = User::factory()->create();

        $empty = $this->repository->summaryForUser($user->id);
        $this->assertSame(['total_items' => 0, 'total_quantity' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'total_value' => 0.0], $empty);

        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 10, 'price' => 1000, 'status' => 'available']);
        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 3, 'min_stock' => 5, 'price' => 500, 'status' => 'low_stock']);
        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 0, 'price' => null, 'status' => 'out_of_stock']);
        Stock::factory()->create(['quantity' => 99]); // another user

        $summary = $this->repository->summaryForUser($user->id);

        $this->assertSame(3, $summary['total_items']);
        $this->assertSame(13, $summary['total_quantity']);
        $this->assertSame(1, $summary['low_stock']);
        $this->assertSame(1, $summary['out_of_stock']);
        $this->assertSame(11500.0, $summary['total_value']);
    }

    public function test_insert_many(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->repository->insertMany([]));

        $records = [
            ['user_id' => $user->id, 'name' => 'Batch 1', 'quantity' => 1, 'status' => 'low_stock', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'name' => 'Batch 2', 'quantity' => 0, 'status' => 'out_of_stock', 'created_at' => now(), 'updated_at' => now()],
        ];

        $this->assertTrue($this->repository->insertMany($records));
        $this->assertDatabaseCount('stocks', 2);
    }
}
