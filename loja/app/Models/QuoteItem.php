<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item) {
            $item->total = max(0, $item->subtotal() - $item->discountAmount());
        });

        static::saved(fn (QuoteItem $item) => $item->quote?->recalculateTotals());
        static::deleted(fn (QuoteItem $item) => $item->quote?->recalculateTotals());
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        return (float) $this->qty * (float) $this->unit_price;
    }

    public function discountAmount(): float
    {
        $subtotal = $this->subtotal();

        $amount = match ($this->discount_type) {
            'percent' => $subtotal * ((float) $this->discount_value / 100),
            'fixed' => ((float) $this->discount_value) * ((float) $this->qty),
            default => 0.0,
        };

        return min($amount, $subtotal);
    }
}
