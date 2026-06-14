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
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item) {
            $item->total = $item->qty * (float) $item->unit_price;
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
}
