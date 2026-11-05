<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedStockItem(
            name: 'Kertas HVS A4',
            sku: 'ATK-HVS-A4',
            description: 'Kertas 80gsm isi 500 lembar',
            quantity: 40,
            unit: 'rim',
            price: 52000.0,
            minStock: 10,
            status: 'available',
        );

        $this->assertSame('Kertas HVS A4', $dto->name);
        $this->assertSame('ATK-HVS-A4', $dto->sku);
        $this->assertSame(40, $dto->quantity);
        $this->assertSame(10, $dto->minStock);

        $this->assertSame([
            'name' => 'Kertas HVS A4',
            'sku' => 'ATK-HVS-A4',
            'description' => 'Kertas 80gsm isi 500 lembar',
            'quantity' => 40,
            'unit' => 'rim',
            'price' => 52000.0,
            'min_stock' => 10,
            'status' => 'available',
        ], $dto->toArray());
    }

    public function test_dto_defaults(): void
    {
        $dto = new GeneratedStockItem('Barang');

        $this->assertSame([
            'name' => 'Barang',
            'sku' => null,
            'description' => null,
            'quantity' => 0,
            'unit' => 'pcs',
            'price' => null,
            'min_stock' => 5,
            'status' => 'available',
        ], $dto->toArray());
    }
}
