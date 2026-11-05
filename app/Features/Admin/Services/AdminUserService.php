<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    /**
     * Get paginated list of all users with their stock summary
     * (item count, total quantity, low-stock & out-of-stock counts).
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount([
                'stocks',
                'stocks as low_stock_count' => fn ($q) => $q->where('status', StockStatus::LOW_STOCK->value),
                'stocks as out_of_stock_count' => fn ($q) => $q->where('status', StockStatus::OUT_OF_STOCK->value),
            ])
            ->withSum('stocks', 'quantity');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get read-only list of stock items for a specific target user.
     */
    public function getUserStocks(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stockRepository->forUser($userId, $status, $search, $perPage);
    }

    /**
     * Get aggregated stock summary for a specific target user.
     *
     * @return array{total_items: int, total_quantity: int, low_stock: int, out_of_stock: int, total_value: float}
     */
    public function getUserStockSummary(int $userId): array
    {
        return $this->stockRepository->summaryForUser($userId);
    }
}
