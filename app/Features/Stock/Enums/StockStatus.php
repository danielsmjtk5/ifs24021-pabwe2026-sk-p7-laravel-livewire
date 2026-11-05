<?php

namespace App\Features\Stock\Enums;

enum StockStatus: string
{
    case AVAILABLE = 'available';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';

    /**
     * Human readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::LOW_STOCK => 'Low Stock',
            self::OUT_OF_STOCK => 'Out of Stock',
        };
    }

    /**
     * Tailwind badge classes (hijau / kuning / merah).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::LOW_STOCK => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            self::OUT_OF_STOCK => 'bg-red-100 text-red-800 border-red-300',
        };
    }

    /**
     * Derive status from quantity versus minimum stock threshold.
     * quantity <= 0 → out_of_stock; quantity <= min_stock → low_stock; otherwise available.
     */
    public static function fromQuantity(int $quantity, int $minStock): self
    {
        if ($quantity <= 0) {
            return self::OUT_OF_STOCK;
        }

        return $quantity <= $minStock ? self::LOW_STOCK : self::AVAILABLE;
    }

    /**
     * Parse a loose status string (e.g. "Low Stock", "low_stock", "LOW-STOCK").
     */
    public static function tryFromLoose(?string $value): ?self
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = (string) preg_replace('/[\s\-]+/', '_', $normalized);

        return self::tryFrom($normalized);
    }

    /**
     * All raw values, useful for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
