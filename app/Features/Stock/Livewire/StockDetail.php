<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    // Edit form state
    public string $name = '';
    public ?string $sku = null;
    public ?string $description = '';
    public mixed $quantity = 0;
    public string $unit = 'pcs';
    public mixed $price = null;
    public mixed $minStock = 5;
    public string $status = ''; // '' = otomatis

    // Quantity adjustment amount (+/-)
    public mixed $adjustAmount = 1;

    public mixed $coverPhoto = null;
    public bool $showEditModal = false;
    public bool $confirmingStockDeletion = false;
    public bool $confirmingCoverDeletion = false;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);

        $this->stock = $stock;
        $this->fillForm();
    }

    /**
     * Populate edit form fields from the current stock model.
     */
    protected function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = $this->stock->sku;
        $this->description = $this->stock->description ?? '';
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price;
        $this->minStock = $this->stock->min_stock;
        // Status manual hanya dipertahankan bila berbeda dari status turunan (otomatis)
        $this->status = $this->stock->status === StockStatus::fromQuantity($this->stock->quantity, $this->stock->min_stock)->value ? '' : $this->stock->status;
    }

    public function openEditModal(): void
    {
        Gate::authorize('update', $this->stock);

        $this->resetErrorBag();
        $this->fillForm();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
    }

    public function updatedCoverPhoto(StockService $stockService): void
    {
        Gate::authorize('update', $this->stock);

        $this->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'], // max 5MB
        ]);

        $stockService->updateCoverPhoto($this->stock, $this->coverPhoto);
        $this->stock->refresh();
        $this->reset('coverPhoto');

        session()->flash('success', 'Cover barang berhasil diperbarui.');
    }

    public function confirmDeleteCover(): void
    {
        $this->confirmingCoverDeletion = true;
    }

    public function cancelDeleteCover(): void
    {
        $this->confirmingCoverDeletion = false;
    }

    public function deleteCoverPhoto(StockService $stockService): void
    {
        Gate::authorize('update', $this->stock);

        $stockService->deleteCoverPhoto($this->stock);
        $this->stock->refresh();
        $this->confirmingCoverDeletion = false;

        session()->flash('success', 'Cover barang berhasil dihapus.');
    }

    public function saveStock(StockService $stockService): void
    {
        Gate::authorize('update', $this->stock);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000000'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'minStock' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status' => ['nullable', 'string', Rule::in(StockStatus::values())],
        ]);

        $stockService->updateStock($this->stock, [
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'],
            'quantity' => (int) $validated['quantity'],
            'unit' => $validated['unit'],
            'price' => $validated['price'],
            'min_stock' => (int) $validated['minStock'],
            'status' => $validated['status'] ?: null,
        ]);

        $this->stock->refresh();
        $this->fillForm();
        $this->closeEditModal();

        session()->flash('success', 'Informasi stok berhasil disimpan.');
    }

    public function increaseQuantity(StockService $stockService): void
    {
        $this->applyAdjustment($stockService, 1);
    }

    public function decreaseQuantity(StockService $stockService): void
    {
        $this->applyAdjustment($stockService, -1);
    }

    /**
     * Validate the adjustment amount and apply it in the given direction.
     */
    protected function applyAdjustment(StockService $stockService, int $direction): void
    {
        Gate::authorize('update', $this->stock);

        $this->validate([
            'adjustAmount' => ['required', 'integer', 'min:1', 'max:100000000'],
        ]);

        $stockService->adjustQuantity($this->stock, $direction * (int) $this->adjustAmount);
        $this->stock->refresh();
        $this->fillForm();

        session()->flash('success', 'Jumlah stok berhasil disesuaikan.');
    }

    public function confirmDeleteStock(): void
    {
        $this->confirmingStockDeletion = true;
    }

    public function cancelDeleteStock(): void
    {
        $this->confirmingStockDeletion = false;
    }

    public function deleteStock(StockService $stockService): mixed
    {
        Gate::authorize('delete', $this->stock);

        $this->confirmingStockDeletion = false;
        $stockService->deleteStock($this->stock);

        session()->flash('success', 'Item stok berhasil dihapus.');

        return redirect()->route('stocks.index');
    }

    public function render(): mixed
    {
        return view('livewire.stock.stock-detail', [
            'statuses' => StockStatus::cases(),
            'canManage' => Gate::allows('update', $this->stock),
        ])->layout('components.layouts.app', ['title' => 'Detail Stok: '.$this->stock->name]);
    }
}
