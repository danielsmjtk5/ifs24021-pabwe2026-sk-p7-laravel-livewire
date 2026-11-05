<?php

namespace Tests\Unit\Enums;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_stock_status_labels(): void
    {
        $this->assertSame('Available', StockStatus::AVAILABLE->label());
        $this->assertSame('Low Stock', StockStatus::LOW_STOCK->label());
        $this->assertSame('Out of Stock', StockStatus::OUT_OF_STOCK->label());
    }

    public function test_stock_status_badge_classes_are_green_yellow_red(): void
    {
        $this->assertStringContainsString('emerald', StockStatus::AVAILABLE->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LOW_STOCK->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OUT_OF_STOCK->badgeClass());
    }

    public function test_from_quantity_derives_status(): void
    {
        $this->assertSame(StockStatus::OUT_OF_STOCK, StockStatus::fromQuantity(0, 5));
        $this->assertSame(StockStatus::OUT_OF_STOCK, StockStatus::fromQuantity(-3, 5));
        $this->assertSame(StockStatus::LOW_STOCK, StockStatus::fromQuantity(1, 5));
        $this->assertSame(StockStatus::LOW_STOCK, StockStatus::fromQuantity(5, 5));
        $this->assertSame(StockStatus::AVAILABLE, StockStatus::fromQuantity(6, 5));
        $this->assertSame(StockStatus::AVAILABLE, StockStatus::fromQuantity(1, 0));
    }

    public function test_try_from_loose_parses_labels_and_values(): void
    {
        $this->assertSame(StockStatus::LOW_STOCK, StockStatus::tryFromLoose('Low Stock'));
        $this->assertSame(StockStatus::OUT_OF_STOCK, StockStatus::tryFromLoose(' out-of-stock '));
        $this->assertSame(StockStatus::AVAILABLE, StockStatus::tryFromLoose('AVAILABLE'));
        $this->assertNull(StockStatus::tryFromLoose('unknown'));
        $this->assertNull(StockStatus::tryFromLoose(null));
    }

    public function test_values_returns_all_raw_values(): void
    {
        $this->assertSame(['available', 'low_stock', 'out_of_stock'], StockStatus::values());
    }
}
