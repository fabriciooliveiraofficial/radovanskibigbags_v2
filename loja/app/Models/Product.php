<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const CONDITIONS = [
        'novo'   => 'Novo',
        'lavado' => 'Lavado',
        'sujo'   => 'Sujo',
        'usado'  => 'Usado',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'weight_kg' => 'decimal:3',
            'price_visible' => 'boolean',
            'has_discharge_valve' => 'boolean',
            'has_liner' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function quantityPrices(): HasMany
    {
        return $this->hasMany(QuantityPrice::class)->orderBy('min_qty');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function useCases(): BelongsToMany
    {
        return $this->belongsToMany(UseCase::class, 'product_use_case');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function coverImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_cover', true) ?? $this->images->first();
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->condition] ?? ucfirst($this->condition);
    }

    /** "90 × 90 × 120 cm" ou null quando sem medidas */
    public function dimensionsLabel(): ?string
    {
        if (! $this->width_cm || ! $this->height_cm) {
            return null;
        }

        $parts = array_filter([$this->width_cm, $this->depth_cm, $this->height_cm]);

        return implode(' × ', $parts).' cm';
    }

    /** Preço formatado ou "Sob consulta", respeitando a configuração do produto */
    public function displayPrice(): string
    {
        if (! $this->price_visible || $this->price === null) {
            return 'Sob consulta';
        }

        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }

    /** Menor preço por quantidade (para exibir "a partir de") */
    public function lowestBulkPrice(): ?float
    {
        $lowest = $this->quantityPrices->min('unit_price');

        return $lowest !== null ? (float) $lowest : null;
    }
}
