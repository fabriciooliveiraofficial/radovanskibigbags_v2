<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Carrinho-cotação em sessão. Não há checkout: o carrinho vira um
 * pedido de orçamento enviado pelo WhatsApp.
 */
class Cart
{
    private const KEY = 'cart.items';

    /** @return array<string, int> chave "productId:variantId" => quantidade */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(int $productId, ?int $variantId, int $qty): void
    {
        $items = $this->raw();
        $key = $productId.':'.($variantId ?? 0);
        $items[$key] = ($items[$key] ?? 0) + max(1, $qty);
        session([self::KEY => $items]);
    }

    public function update(int $productId, ?int $variantId, int $qty): void
    {
        $items = $this->raw();
        $key = $productId.':'.($variantId ?? 0);

        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key] = $qty;
        }

        session([self::KEY => $items]);
    }

    public function remove(int $productId, ?int $variantId): void
    {
        $this->update($productId, $variantId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Itens hidratados com produto/variação.
     *
     * @return Collection<int, array{product: Product, variant: ?ProductVariant, qty: int, unit_price: ?float, key: string}>
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if ($raw === []) {
            return collect();
        }

        $productIds = collect(array_keys($raw))->map(fn ($key) => (int) explode(':', $key)[0])->unique();
        $products = Product::with(['images', 'quantityPrices'])->whereIn('id', $productIds)->get()->keyBy('id');

        $variantIds = collect(array_keys($raw))
            ->map(fn ($key) => (int) explode(':', $key)[1])
            ->filter()
            ->unique();
        $variants = $variantIds->isEmpty()
            ? collect()
            : ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        return collect($raw)
            ->map(function (int $qty, string $key) use ($products, $variants) {
                [$productId, $variantId] = array_map('intval', explode(':', $key));
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                $variant = $variantId ? $variants->get($variantId) : null;

                return [
                    'key' => $key,
                    'product' => $product,
                    'variant' => $variant,
                    'qty' => $qty,
                    'unit_price' => $this->unitPrice($product, $variant, $qty),
                ];
            })
            ->filter()
            ->values();
    }

    /** Preço unitário considerando faixas de quantidade; null quando "sob consulta" */
    public function unitPrice(Product $product, ?ProductVariant $variant, int $qty): ?float
    {
        if (! $product->price_visible) {
            return null;
        }

        $bulk = $product->quantityPrices
            ->when($variant, fn ($prices) => $prices->where('product_variant_id', $variant->id)->whenEmpty(fn () => $product->quantityPrices->whereNull('product_variant_id')))
            ->where('min_qty', '<=', $qty)
            ->sortByDesc('min_qty')
            ->first();

        if ($bulk) {
            return (float) $bulk->unit_price;
        }

        if ($variant && $variant->price !== null) {
            return (float) $variant->price;
        }

        return $product->price !== null ? (float) $product->price : null;
    }
}
