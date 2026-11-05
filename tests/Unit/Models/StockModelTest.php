<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $stock->user);
        $this->assertEquals($user->id, $stock->user->id);
    }

    public function test_stock_casts_and_defaults(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create(['user_id' => $user->id, 'name' => 'Default Item']);
        $stock->refresh();

        $this->assertSame(0, $stock->quantity);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame('available', $stock->status);
        $this->assertNull($stock->price);

        $priced = Stock::factory()->create(['price' => 1500]);
        $this->assertSame('1500.00', $priced->fresh()->price);
    }

    public function test_cascade_delete_with_user(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_factory_states(): void
    {
        $low = Stock::factory()->lowStock()->create();
        $this->assertSame('low_stock', $low->status);
        $this->assertSame(3, $low->quantity);

        $out = Stock::factory()->outOfStock()->create();
        $this->assertSame('out_of_stock', $out->status);
        $this->assertSame(0, $out->quantity);
    }

    public function test_status_enum_and_fallback(): void
    {
        $stock = Stock::factory()->lowStock()->make();
        $this->assertSame(StockStatus::LOW_STOCK, $stock->statusEnum());

        $unknown = new Stock(['status' => 'weird', 'quantity' => 0, 'min_stock' => 5]);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $unknown->statusEnum());
    }

    public function test_total_value(): void
    {
        $this->assertNull((new Stock(['quantity' => 10, 'price' => null]))->totalValue());
        $this->assertSame(25000.0, (new Stock(['quantity' => 10, 'price' => 2500]))->totalValue());
    }

    public function test_cover_image_url_returns_correct_url_or_null(): void
    {
        $withoutCover = Stock::factory()->create(['cover_image_path' => null]);
        $this->assertNull($withoutCover->coverImageUrl());

        Storage::fake('public');
        Storage::disk('public')->put('stock-covers/sample.png', 'content');

        $withCover = Stock::factory()->create(['cover_image_path' => 'stock-covers/sample.png']);
        $this->assertStringContainsString('stock-covers/sample.png', (string) $withCover->coverImageUrl());

        $missingFile = Stock::factory()->create(['cover_image_path' => 'stock-covers/missing.png']);
        $this->assertNull($missingFile->coverImageUrl());
    }
}
