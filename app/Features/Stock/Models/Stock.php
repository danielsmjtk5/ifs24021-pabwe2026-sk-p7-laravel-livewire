<?php

namespace App\Features\Stock\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'name',
    'sku',
    'description',
    'quantity',
    'unit',
    'price',
    'min_stock',
    'status',
    'cover_image_path',
])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stocks';

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 0,
        'unit' => 'pcs',
        'min_stock' => 5,
        'status' => 'available',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_stock' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the stock item.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Status as enum instance (falls back to derived status for unknown values).
     */
    public function statusEnum(): StockStatus
    {
        return StockStatus::tryFrom((string) $this->status)
            ?? StockStatus::fromQuantity((int) $this->quantity, (int) $this->min_stock);
    }

    /**
     * Total inventory value (quantity × price), null when price is unknown.
     */
    public function totalValue(): ?float
    {
        if ($this->price === null) {
            return null;
        }

        return round((float) $this->price * (int) $this->quantity, 2);
    }

    /**
     * Get the public URL for the cover image.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path && Storage::disk('public')->exists($this->cover_image_path)) {
            return Storage::disk('public')->url($this->cover_image_path);
        }

        return null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockFactory
    {
        return StockFactory::new();
    }
}
