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
        'retirada'        => 'Retirada no local',
        'entrega_propria' => 'Entrega própria',
        'transportadora'  => 'Transportadora',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_until'    => 'date',
            'subtotal'       => 'decimal:2',
            'discount_value' => 'decimal:2',
            'shipping_cost'  => 'decimal:2',
            'total_weight_kg'=> 'decimal:3',
            'total'          => 'decimal:2',
            'sent_at'        => 'datetime',
            'viewed_at'      => 'datetime',
            'approved_at'    => 'datetime',
            'sent_channels'  => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            $quote->type         ??= 'orcamento';
            $quote->number       ??= static::nextNumber($quote->type);
            $quote->public_token ??= Str::random(12);
        });
    }

    public static function nextNumber(string $type = 'orcamento'): string
    {
        $prefix = $type === 'pedido' ? 'OC' : 'ORC';

        // Sequência compartilhada: ORC-0001606 e OC-0001606 usam o mesmo número
        $lastOrc = static::where('number', 'like', 'ORC-%')->orderByDesc('number')->value('number');
        $lastOc  = static::where('number', 'like', 'OC-%')->orderByDesc('number')->value('number');

        $seqOrc = $lastOrc ? (int) Str::afterLast($lastOrc, '-') : 0;
        $seqOc  = $lastOc  ? (int) Str::afterLast($lastOc, '-')  : 0;

        $seq = max($seqOrc, $seqOc, 1605) + 1;

        return sprintf('%s-%07d', $prefix, $seq);
    }

    public function isPedido(): bool
    {
        return $this->type === 'pedido';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuoteEvent::class)->latest('created_at');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    public function publicUrl(): string
    {
        return route('quote.public', $this->public_token);
    }

    public function totalVolumeCbm(): ?float
    {
        $vol = 0.0;
        foreach ($this->items as $item) {
            $p = $item->product;
            if ($p && $p->width_cm && $p->height_cm) {
                $w = (float) $p->width_cm / 100;
                $d = $p->depth_cm ? (float) $p->depth_cm / 100 : $w;
                $h = (float) $p->height_cm / 100;
                $vol += $w * $d * $h * $item->qty;
            }
        }

        return $vol > 0 ? round($vol, 3) : null;
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('total');

        $discount = match ($this->discount_type) {
            'percent' => $subtotal * ((float) $this->discount_value / 100),
            'fixed'   => (float) $this->discount_value,
            default   => 0.0,
        };

        $totalWeight = (float) $this->items()->getModel()->newQuery()
            ->where('quote_id', $this->id)
            ->join('products', 'products.id', '=', 'quote_items.product_id')
            ->selectRaw('SUM(quote_items.qty * COALESCE(quote_items.weight_kg, products.weight_kg, 0)) as w')
            ->value('w');

        $this->forceFill([
            'subtotal'        => $subtotal,
            'total'           => max(0, $subtotal - $discount + (float) $this->shipping_cost),
            'total_weight_kg' => $totalWeight > 0 ? $totalWeight : null,
        ])->saveQuietly();
    }

    public function discountAmount(): float
    {
        return match ($this->discount_type) {
            'percent' => (float) $this->subtotal * ((float) $this->discount_value / 100),
            'fixed'   => (float) $this->discount_value,
            default   => 0.0,
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
