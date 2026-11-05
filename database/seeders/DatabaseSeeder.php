<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        $samples = [
            [
                'name' => 'Kertas HVS A4 80gsm',
                'sku' => 'ATK-HVS-A4',
                'description' => "Kertas HVS ukuran **A4** 80gsm.\n\n- Isi 500 lembar per rim\n- Cocok untuk printer laser & inkjet",
                'quantity' => 40,
                'unit' => 'rim',
                'price' => 52000,
                'min_stock' => 10,
            ],
            [
                'name' => 'Tinta Printer Hitam',
                'sku' => 'ATK-INK-BLK',
                'description' => 'Tinta refill hitam 100ml untuk printer inkjet.',
                'quantity' => 4,
                'unit' => 'botol',
                'price' => 35000,
                'min_stock' => 5,
            ],
            [
                'name' => 'Mouse Wireless',
                'sku' => 'ELK-MSE-WL',
                'description' => 'Mouse wireless 2.4GHz dengan receiver USB.',
                'quantity' => 0,
                'unit' => 'pcs',
                'price' => 85000,
                'min_stock' => 3,
            ],
            [
                'name' => 'Kabel LAN Cat6',
                'sku' => 'NET-CAT6-305',
                'description' => 'Kabel UTP Cat6 per roll 305 meter.',
                'quantity' => 12,
                'unit' => 'roll',
                'price' => 1250000,
                'min_stock' => 2,
            ],
        ];

        foreach ($samples as $sample) {
            Stock::firstOrCreate(
                ['user_id' => $member->id, 'sku' => $sample['sku']],
                $sample + [
                    'status' => StockStatus::fromQuantity($sample['quantity'], $sample['min_stock'])->value,
                ]
            );
        }
    }
}
