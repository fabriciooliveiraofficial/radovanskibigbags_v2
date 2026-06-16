<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class OrderGenerator
{
    public function fromQuoteRequest(QuoteRequest $qr, array $options = []): Quote
    {
        // 1. Garante cliente
        $customer = $this->resolveCustomer($qr);

        // 2. Cria o Quote tipo "pedido"
        $quote = Quote::create([
            'type'             => 'pedido',
            'customer_id'      => $customer->id,
            'quote_request_id' => $qr->id,
            'status'           => 'rascunho',
            'valid_until'      => now()->addDays(7),
            'payment_terms'    => $qr->payment_method === 'boleto'
                ? 'Boleto '.(($qr->payment_term_days ?? '30/45/60').' dias')
                : null,
            'notes'            => $options['notes'] ?? null,
            'shipping_method'  => 'retirada',
            'delivery_days'    => $options['delivery_days'] ?? null,
        ]);

        // 3. Cria itens com snapshot de preço por quantidade (desconto por volume)
        $sort = 0;
        foreach ($qr->items as $raw) {
            $product = Product::with(['quantityPrices', 'images'])->find($raw['product_id']);
            if (! $product) {
                continue;
            }

            $variant  = isset($raw['variant_id']) ? ProductVariant::find($raw['variant_id']) : null;
            $qty      = (int) $raw['qty'];
            $price    = $this->resolvePrice($product, $variant, $qty);
            $label    = $product->name;
            if ($variant) {
                $label .= ' — '.$variant->name;
            }

            $quote->items()->create([
                'product_id'         => $product->id,
                'product_variant_id' => $variant?->id,
                'description'        => $label,
                'qty'                => $qty,
                'unit_price'         => $price ?? 0,
                'weight_kg'          => $product->weight_kg,
                'sort_order'         => $sort++,
            ]);
        }

        // 4. Calcula frete automático se tiver CEP
        $cep = $options['cep'] ?? ($customer->cep ?? null);
        if ($cep) {
            $this->applyFreight($quote, $cep);
        }

        // 5. Recalcula totais (inclui peso)
        $quote->recalculateTotals();

        // 6. Marca QuoteRequest como atendido se ainda estiver novo
        if ($qr->status === 'novo') {
            $qr->forceFill(['status' => 'atendido'])->save();
        }

        return $quote->fresh(['items', 'customer']);
    }

    private function resolveCustomer(QuoteRequest $qr): Customer
    {
        // Se tem CreditApplication aprovada, usa o customer vinculado
        $creditApp = $qr->creditApplication;
        if ($creditApp && $creditApp->customer_id) {
            return Customer::findOrFail($creditApp->customer_id);
        }

        // Busca por telefone ou cria
        $phone = preg_replace('/\D/', '', (string) ($qr->phone ?? ''));

        if ($phone) {
            $customer = Customer::where('phone', $phone)->first();
            if ($customer) {
                return $customer;
            }
        }

        return Customer::create([
            'name'  => $qr->name ?? 'Cliente',
            'phone' => $qr->phone ?? '',
            'city'  => $qr->city  ?? 'Curitiba',
            'state' => 'PR',
        ]);
    }

    private function resolvePrice(Product $product, ?ProductVariant $variant, int $qty): ?float
    {
        if (! $product->price_visible) {
            return null;
        }

        $bulk = $product->quantityPrices
            ->when($variant, fn ($prices) => $prices->where('product_variant_id', $variant->id)
                ->whenEmpty(fn () => $product->quantityPrices->whereNull('product_variant_id')))
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

    private function applyFreight(Quote $quote, string $cep): void
    {
        $clean = preg_replace('/\D/', '', $cep);

        $storeFreight = Setting::get('freight_price');
        if ($storeFreight !== null) {
            $quote->forceFill([
                'shipping_method' => 'entrega_propria',
                'shipping_cost'   => (float) $storeFreight,
                'shipping_cep'    => $clean,
            ])->saveQuietly();

            return;
        }

        // Fallback: tenta API de frete via CEP (ViaCEP só retorna endereço, não frete real)
        // Fica retirada se não houver tabela de frete configurada
        $quote->forceFill(['shipping_cep' => $clean])->saveQuietly();
    }
}
