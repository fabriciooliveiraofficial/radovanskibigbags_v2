<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    public const STATUSES = [
        'rascunho'    => 'Rascunho',
        'enviado'     => 'Enviado',
        'visualizado' => 'Visualizado',
        'aprovado'    => 'Aprovado',
        'recusado'    => 'Recusado',
        'expirado'    => 'Expirado',
    ];

    public const SHIPPING_METHODS = [
        'retirada'       => 'Retirada no local',
        'entrega_propria' => 'Entrega própria',
        'transportadora' => 'Transportadora',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'sent_channels' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            $quote->number ??= static::nextNumber();
            $quote->public_token ??= Str::random(12);
        });
    }

    public static function nextNumber(): string
    {
        $year = now()->year;
        $last = static::where('number', 'like', "ORC-{$year}-%")
            ->orderByDesc('number')
            ->value('number');

        $seq = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('ORC-%d-%04d', $year, $seq);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuoteEvent::class)->latest('created_at');
    }

    public function publicUrl(): string
    {
        return route('quote.public', $this->public_token);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('total');

        $discount = match ($this->discount_type) {
            'percent' => $subtotal * ((float) $this->discount_value / 100),
            'fixed' => (float) $this->discount_value,
            default => 0.0,
        };

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount + (float) $this->shipping_cost),
        ])->saveQuietly();
    }

    public function discountAmount(): float
    {
        return match ($this->discount_type) {
            'percent' => (float) $this->subtotal * ((float) $this->discount_value / 100),
            'fixed' => (float) $this->discount_value,
            default => 0.0,
        };
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast() && $this->status !== 'aprovado';
    }

    public function markViewed(array $meta = []): void
    {
        if ($this->status === 'enviado') {
            $this->forceFill(['status' => 'visualizado', 'viewed_at' => $this->viewed_at ?? now()])->saveQuietly();
        } elseif ($this->viewed_at === null) {
            $this->forceFill(['viewed_at' => now()])->saveQuietly();
        }

        $this->events()->create(['type' => 'viewed', 'meta' => $meta]);
    }

    public function markApproved(array $meta = []): void
    {
        $this->forceFill(['status' => 'aprovado', 'approved_at' => now()])->saveQuietly();
        $this->events()->create(['type' => 'approved', 'meta' => $meta]);
    }
}
