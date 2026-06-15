<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\Cart;
use App\Services\Shipping\FreightCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NewQuoteRequestAlert;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart)
    {
    }

    public function index(): View
    {
        return view('store.cart', [
            'items' => $this->cart->items(),
            'freight' => session('cart.freight'),
            'freightCep' => session('cart.freight_cep'),
        ]);
    }

    /** Calcula opções de frete para o CEP informado e guarda na sessão */
    public function freight(Request $request, FreightCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
        ], [
            'cep.regex' => 'Informe um CEP válido (ex: 80000-000).',
        ]);

        $items = $this->cart->items()->map(fn (array $item) => [
            'weight_kg' => (float) ($item['variant']?->weight_kg ?? $item['product']->weight_kg ?? 0.5),
            'qty' => $item['qty'],
        ])->all();

        $result = $calculator->quote($data['cep'], $items);

        session([
            'cart.freight' => $result,
            'cart.freight_cep' => $data['cep'],
        ]);

        return redirect()->route('cart.index');
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->cart->add((int) $data['product_id'], $data['variant_id'] ?? null, (int) ($data['qty'] ?? 1));

        return redirect()
            ->route('cart.index')
            ->with('status', 'Produto adicionado à sua cotação.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'qty' => ['required', 'integer', 'min:0'],
        ]);

        $this->cart->update((int) $data['product_id'], $data['variant_id'] ?? null, (int) $data['qty']);

        return redirect()->route('cart.index');
    }

    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $this->cart->remove((int) $data['product_id'], $data['variant_id'] ?? null);

        return redirect()->route('cart.index');
    }

    /**
     * Registra o pedido de orçamento e redireciona para o WhatsApp da loja
     * com a mensagem montada.
     */
    public function whatsapp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('products.index');
        }

        $quoteRequest = QuoteRequest::create([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'items' => $items->map(fn (array $item) => [
                'product_id' => $item['product']->id,
                'variant_id' => $item['variant']?->id,
                'qty' => $item['qty'],
            ])->all(),
        ]);

        // Dispara e-mail de alerta para o administrador
        $adminEmail = Setting::get('store_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new NewQuoteRequestAlert($quoteRequest));
            } catch (\Exception $e) {
                Log::error("Failed to send NewQuoteRequestAlert: " . $e->getMessage());
            }
        }

        $message = $this->buildWhatsAppMessage($items, $data);

        $storePhone = preg_replace('/\D/', '', (string) Setting::get('store_whatsapp', ''));
        if ($storePhone && ! str_starts_with($storePhone, '55')) {
            $storePhone = '55'.$storePhone;
        }

        $this->cart->clear();

        return redirect()->away('https://wa.me/'.$storePhone.'?text='.rawurlencode($message));
    }

    /**
     * Monta a mensagem de orçamento detalhada (itens, especificações, subtotal,
     * frete e total) enviada ao WhatsApp da loja.
     */
    private function buildWhatsAppMessage(Collection $items, array $data): string
    {
        $freight = session('cart.freight');
        $freightCep = session('cart.freight_cep');

        $lines = ['👋 Olá! Gostaria de um orçamento para os itens abaixo:', ''];
        $lines[] = '━━━━━━━━━━━━━━━';
        $lines[] = '📦 *ITENS*';
        $lines[] = '';

        $subtotal = 0.0;
        $hasConsulta = false;
        $n = 0;

        foreach ($items as $item) {
            $n++;
            $product = $item['product'];
            $variant = $item['variant'];
            $qty = $item['qty'];
            $unitPrice = $item['unit_price'];
            $unit = $product->unit ?: 'un';

            $lines[] = $n.'. *'.$product->name.'*';

            $specs = [];
            if ($variant) {
                $specs[] = $variant->name;
            } else {
                if ($product->dimensionsLabel()) {
                    $specs[] = $product->dimensionsLabel();
                }
                if ($product->capacity_kg) {
                    $specs[] = number_format((float) $product->capacity_kg, 0, ',', '.').' kg';
                }
            }
            $specs[] = $product->conditionLabel();
            $lines[] = '   📐 '.implode(' · ', array_filter($specs));

            if ($unitPrice !== null) {
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;
                $lines[] = '   🔢 '.$qty.' '.$unit.' × '.format_brl($unitPrice).' = *'.format_brl($lineTotal).'*';
            } else {
                $hasConsulta = true;
                $lines[] = '   🔢 '.$qty.' '.$unit.' — *sob consulta*';
            }

            $lines[] = '   🔗 '.route('products.show', $product);
            $lines[] = '';
        }

        $lines[] = '━━━━━━━━━━━━━━━';
        $subtotalLine = '💰 *Subtotal: '.format_brl($subtotal).'*';
        if ($hasConsulta) {
            $subtotalLine .= ' _(itens sob consulta não inclusos)_';
        }
        $lines[] = $subtotalLine;

        if (! empty($freight['options'])) {
            $lines[] = '';
            $freightTitle = '🚚 *FRETE*';
            if ($freightCep) {
                $freightTitle .= ' (CEP '.$freightCep.')';
            }
            $lines[] = $freightTitle;

            $totalLines = [];
            foreach ($freight['options'] as $option) {
                $icon = match ($option['method']) {
                    'retirada' => '🏬',
                    'entrega_propria' => '🚛',
                    'transportadora' => '📦',
                    default => '💬',
                };

                if ($option['cost'] === null) {
                    // O label já descreve "sob consulta" — não repete o custo.
                    $line = '   '.$icon.' '.$option['label'];
                } else {
                    $cost = $option['cost'] == 0.0 ? 'Grátis' : format_brl($option['cost']);
                    $line = '   '.$icon.' '.$option['label'].' — '.$cost;
                    $totalLines[] = '   '.$option['label'].': *'.format_brl($subtotal + $option['cost']).'*';
                }

                if ($option['deadline']) {
                    $line .= ' ('.$option['deadline'].')';
                }
                $lines[] = $line;
            }

            if (! empty($totalLines)) {
                $lines[] = '';
                $lines[] = '✅ *TOTAL COM ENTREGA*';
                array_push($lines, ...$totalLines);
            }
        }

        $lines[] = '';
        $lines[] = '━━━━━━━━━━━━━━━';
        if (! empty($data['name'])) {
            $lines[] = '👤 Nome: '.$data['name'];
        }
        if (! empty($data['city'])) {
            $lines[] = '📍 Cidade: '.$data['city'];
        }
        if (! empty($data['phone'])) {
            $lines[] = '📱 Contato: '.$data['phone'];
        }

        $lines[] = '';
        $lines[] = '_Valores finais confirmados pela nossa equipe._';

        return implode("\n", $lines);
    }
}
