<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    /**
     * Get paginated stock items for the specified user.
     */
    public function getStocksForUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stockRepository->forUser($userId, $status, $search, $perPage);
    }

    /**
     * Get aggregated inventory statistics for the specified user.
     *
     * @return array{total_items: int, total_quantity: int, low_stock: int, out_of_stock: int, total_value: float}
     */
    public function getSummaryForUser(int $userId): array
    {
        return $this->stockRepository->summaryForUser($userId);
    }

    /**
     * Create a new stock item for the user.
     *
     * @param array<string, mixed> $data
     */
    public function createStock(int $userId, array $data): Stock
    {
        $quantity = max(0, (int) ($data['quantity'] ?? 0));
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));

        return $this->stockRepository->create([
            'user_id' => $userId,
            'name' => $data['name'],
            'sku' => $this->nullIfBlank($data['sku'] ?? null),
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'quantity' => $quantity,
            'unit' => $this->nullIfBlank($data['unit'] ?? null) ?? 'pcs',
            'price' => $this->normalizePrice($data['price'] ?? null),
            'min_stock' => $minStock,
            'status' => $this->resolveStatus($quantity, $minStock, $data['status'] ?? null),
            'cover_image_path' => $data['cover_image_path'] ?? null,
        ]);
    }

    /**
     * Update an existing stock item. Status is re-synchronised from quantity/min_stock
     * unless an explicit valid status is supplied.
     *
     * @param array<string, mixed> $data
     */
    public function updateStock(Stock $stock, array $data): bool
    {
        $quantity = max(0, (int) ($data['quantity'] ?? $stock->quantity));
        $minStock = max(0, (int) ($data['min_stock'] ?? $stock->min_stock));

        return $this->stockRepository->update($stock, [
            'name' => $data['name'] ?? $stock->name,
            'sku' => array_key_exists('sku', $data) ? $this->nullIfBlank($data['sku']) : $stock->sku,
            'description' => array_key_exists('description', $data) ? $this->nullIfBlank($data['description']) : $stock->description,
            'quantity' => $quantity,
            'unit' => $this->nullIfBlank($data['unit'] ?? null) ?? $stock->unit,
            'price' => array_key_exists('price', $data) ? $this->normalizePrice($data['price']) : $stock->price,
            'min_stock' => $minStock,
            'status' => $this->resolveStatus($quantity, $minStock, $data['status'] ?? null),
        ]);
    }

    /**
     * Adjust quantity by a delta (+/-), never below zero, and sync status automatically.
     */
    public function adjustQuantity(Stock $stock, int $delta): bool
    {
        $newQuantity = max(0, (int) $stock->quantity + $delta);

        return $this->stockRepository->update($stock, [
            'quantity' => $newQuantity,
            'status' => StockStatus::fromQuantity($newQuantity, (int) $stock->min_stock)->value,
        ]);
    }

    /**
     * Resolve the status to persist: explicit valid status wins, otherwise derived.
     */
    public function resolveStatus(int $quantity, int $minStock, ?string $status = null): string
    {
        $explicit = StockStatus::tryFrom((string) $status);

        return ($explicit ?? StockStatus::fromQuantity($quantity, $minStock))->value;
    }

    /**
     * Update or upload a cover photo for the stock item.
     */
    public function updateCoverPhoto(Stock $stock, mixed $file): string
    {
        $this->deleteCoverFile($stock);

        $path = $file->store('stock-covers', 'public');
        $this->stockRepository->update($stock, ['cover_image_path' => $path]);

        return $path;
    }

    /**
     * Delete the cover photo of the stock item.
     */
    public function deleteCoverPhoto(Stock $stock): bool
    {
        $this->deleteCoverFile($stock);

        return $this->stockRepository->update($stock, ['cover_image_path' => null]);
    }

    /**
     * Delete a stock item and its cover photo if exists.
     */
    public function deleteStock(Stock $stock): bool
    {
        $this->deleteCoverFile($stock);

        return $this->stockRepository->delete($stock);
    }

    /**
     * Remove the stored cover file from the public disk if present.
     */
    protected function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path && Storage::disk('public')->exists($stock->cover_image_path)) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }

    /**
     * Normalise a price value: blank → null, otherwise rounded float.
     */
    protected function normalizePrice(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : round((float) $value, 2);
    }

    /**
     * Convert blank strings to null.
     */
    protected function nullIfBlank(mixed $value): ?string
    {
        $value = $value === null ? null : trim((string) $value);

        return $value === '' ? null : $value;
    }
}
