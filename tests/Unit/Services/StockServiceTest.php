<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockService(new StockRepository());
    }

    public function test_get_stocks_and_summary_for_user(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->create(['user_id' => $user->id, 'quantity' => 10]);

        $this->assertCount(2, $this->service->getStocksForUser($user->id));
        $this->assertSame(20, $this->service->getSummaryForUser($user->id)['total_quantity']);
    }

    public function test_create_stock_normalises_input_and_derives_status(): void
    {
        $user = User::factory()->create();

        $stock = $this->service->createStock($user->id, [
            'name' => 'Service Stock',
            'sku' => '  ',
            'description' => '',
            'quantity' => -7,
            'unit' => '',
            'price' => '',
            'min_stock' => 5,
        ]);

        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame('pcs', $stock->unit);
        $this->assertNull($stock->price);
        $this->assertSame('out_of_stock', $stock->status);

        $minimal = $this->service->createStock($user->id, ['name' => 'Minimal']);
        $this->assertSame(0, $minimal->quantity);
        $this->assertSame(5, $minimal->min_stock);
        $this->assertSame('out_of_stock', $minimal->status);

        $priced = $this->service->createStock($user->id, [
            'name' => 'Priced',
            'sku' => ' SKU-1 ',
            'quantity' => 50,
            'unit' => 'box',
            'price' => '12345.678',
            'min_stock' => 10,
            'status' => 'low_stock',
        ]);
        $this->assertSame('SKU-1', $priced->sku);
        $this->assertSame('12345.68', $priced->fresh()->price);
        $this->assertSame('low_stock', $priced->status); // explicit status wins
    }

    public function test_update_stock_merges_and_resyncs_status(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'sku' => 'OLD',
            'description' => 'Old desc',
            'quantity' => 20,
            'unit' => 'box',
            'price' => 1000,
            'min_stock' => 5,
            'status' => 'available',
        ]);

        // Partial update: keep untouched fields, derive status from new quantity
        $this->assertTrue($this->service->updateStock($stock, ['name' => 'New Name', 'quantity' => 2]));
        $stock->refresh();
        $this->assertSame('New Name', $stock->name);
        $this->assertSame('OLD', $stock->sku);
        $this->assertSame('Old desc', $stock->description);
        $this->assertSame('box', $stock->unit);
        $this->assertSame('1000.00', $stock->price);
        $this->assertSame('low_stock', $stock->status);

        // Explicit fields incl. clearing nullable ones
        $this->service->updateStock($stock, [
            'sku' => '',
            'description' => null,
            'price' => null,
            'unit' => 'kg',
            'min_stock' => 1,
            'status' => 'out_of_stock',
        ]);
        $stock->refresh();
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame('kg', $stock->unit);
        $this->assertSame(1, $stock->min_stock);
        $this->assertSame('out_of_stock', $stock->status);
    }

    public function test_adjust_quantity(): void
    {
        $stock = Stock::factory()->create(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);

        $this->assertTrue($this->service->adjustQuantity($stock, -1));
        $this->assertSame(5, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::LOW_STOCK->value, $stock->fresh()->status);

        $this->service->adjustQuantity($stock->fresh(), -100);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::OUT_OF_STOCK->value, $stock->fresh()->status);

        $this->service->adjustQuantity($stock->fresh(), 25);
        $this->assertSame(25, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::AVAILABLE->value, $stock->fresh()->status);
    }

    public function test_resolve_status(): void
    {
        $this->assertSame('available', $this->service->resolveStatus(10, 5));
        $this->assertSame('low_stock', $this->service->resolveStatus(5, 5, ''));
        $this->assertSame('out_of_stock', $this->service->resolveStatus(0, 5, 'not-a-status'));
        $this->assertSame('low_stock', $this->service->resolveStatus(100, 5, 'low_stock'));
    }

    public function test_delete_stock(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->service->deleteStock($stock));
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_cover_photo_management(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create();

        $path = $this->service->updateCoverPhoto($stock, UploadedFile::fake()->image('cover.jpg'));
        $stock->refresh();
        $this->assertSame($path, $stock->cover_image_path);
        Storage::disk('public')->assertExists($path);

        // Replace cover deletes the old file
        $newPath = $this->service->updateCoverPhoto($stock, UploadedFile::fake()->image('new-cover.jpg'));
        $stock->refresh();
        $this->assertSame($newPath, $stock->cover_image_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);

        // Delete cover
        $this->assertTrue($this->service->deleteCoverPhoto($stock));
        $stock->refresh();
        $this->assertNull($stock->cover_image_path);
        Storage::disk('public')->assertMissing($newPath);

        // Deleting the stock cleans up its cover
        $anotherPath = $this->service->updateCoverPhoto($stock, UploadedFile::fake()->image('another.jpg'));
        Storage::disk('public')->assertExists($anotherPath);
        $this->service->deleteStock($stock->fresh());
        Storage::disk('public')->assertMissing($anotherPath);
    }
}
