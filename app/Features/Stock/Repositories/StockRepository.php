<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    /**
     * Get paginated stock items for a given user with optional status and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Stock::query()->where('user_id', $userId);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get all stock items for a given user without pagination.
     *
     * @return Collection<int, Stock>
     */
    public function allForUser(int $userId): Collection
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    /**
     * Aggregate inventory statistics for a user.
     *
     * @return array{total_items: int, total_quantity: int, low_stock: int, out_of_stock: int, total_value: float}
     */
    public function summaryForUser(int $userId): array
    {
        $row = Stock::query()
            ->where('user_id', $userId)
            ->selectRaw(
                'COUNT(*) as total_items, '
                .'COALESCE(SUM(quantity), 0) as total_quantity, '
                .'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as low_stock, '
                .'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as out_of_stock, '
                .'COALESCE(SUM(quantity * COALESCE(price, 0)), 0) as total_value',
                [StockStatus::LOW_STOCK->value, StockStatus::OUT_OF_STOCK->value]
            )
            ->toBase()
            ->first();

        return [
            'total_items' => (int) $row->total_items,
            'total_quantity' => (int) $row->total_quantity,
            'low_stock' => (int) $row->low_stock,
            'out_of_stock' => (int) $row->out_of_stock,
            'total_value' => round((float) $row->total_value, 2),
        ];
    }

    /**
     * Find a stock item by id.
     */
    public function find(int $id): ?Stock
    {
        return Stock::find($id);
    }

    /**
     * Find a stock item by id or throw an exception.
     */
    public function findOrFail(int $id): Stock
    {
        return Stock::findOrFail($id);
    }

    /**
     * Create a new stock item.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Stock
    {
        return Stock::create($attributes);
    }

    /**
     * Update an existing stock item.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Stock $stock, array $attributes): bool
    {
        return $stock->update($attributes);
    }

    /**
     * Delete an existing stock item.
     */
    public function delete(Stock $stock): bool
    {
        return (bool) $stock->delete();
    }

    /**
     * Insert multiple stock items at once.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function insertMany(array $records): bool
    {
        if (empty($records)) {
            return true;
        }

        return Stock::insert($records);
    }
}
