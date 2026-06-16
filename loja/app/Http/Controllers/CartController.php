<?php

namespace App\Http\Controllers;

use App\Models\CreditApplication;
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
        $creditApplication = null;

        if ($creditApplicationId = session('cart.credit_application_id')) {
            $creditApplication = CreditApplication::where('id', $creditApplicationId)
                ->where('status', 'aprovado')
                ->first();

            if (! $creditApplication) {
                session()->forget('cart.credit_application_id');
            }
        }

        return view('store.cart', [
            'items' => $this->cart->items(),
            'freight' => session('cart.freight'),
            'freightCep' => session('cart.freight_cep'),
            'creditApplication' => $creditApplication,
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
            ->with('status', 'Produto adicionado ao seu pedido.');
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
     * Verifica se o CNPJ informado tem ficha cadastral aprovada e, em caso
     * positivo, libera a opção "Pagar com boleto" no fechamento do pedido.
     */
    public function checkBoleto(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cnpj' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! cnpj_is_valid($value)) {
                    $fail('Informe um CNPJ válido.');
                }
            }],
        ], [
            'cnpj.required' => 'Informe o CNPJ da empresa.',
        ]);

        $cnpjDigits = preg_replace('/\D/', '', $data['cnpj']);

        $creditApplication = CreditApplication::where('document', $cnpjDigits)
            ->where('status', 'aprovado')
            ->first();

        if (! $creditApplication) {
            return redirect()->route('cart.index')->with('boleto_not_found', true);
        }

        session(['cart.credit_application_id' => $creditApplication->id]);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Empresa aprovada para boleto! Escolha essa opção ao finalizar o pedido.');
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
            'payment_method' => ['nullable', 'in:whatsapp,boleto'],
        ]);

        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('products.index');
        }

        $paymentMethod = 'whatsapp';
        $creditApplicationId = null;

        if (($data['payment_method'] ?? 'whatsapp') === 'boleto') {
            $creditApplication = CreditApplication::find(session('cart.credit_application_id'));

            if ($creditApplication && $creditApplication->status === 'aprovado') {
                $paymentMethod = 'boleto';
                $creditApplicationId = $creditApplication->id;
            }
        }

        $data['payment_method'] = $paymentMethod;

        $quoteRequest = QuoteRequest::create([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'items' => $items->map(fn (array $item) => [
                'product_id' => $item['product']->id,
                'variant_id' => $item['variant']?->id,
                'qty' => $item['qty'],
            ])->all(),
            'payment_method' => $paymentMethod,
            'credit_application_id' => $creditApplicationId,
            'boleto_status' => $paymentMethod === 'boleto' ? 'aguardando_aprovacao' : null,
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
        session()->forget('cart.credit_application_id');

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

        $lines = ["\u{1F44B} Olá! Quero fazer um pedido dos itens abaixo:", ''];
        $lines[] = '━━━━━━━━━━━━━━━';
        $lines[] = "\u{1F4E6} *ITENS*";
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
            $lines[] = "   \u{1F4D0} ".implode(' · ', array_filter($specs));

            if ($unitPrice !== null) {
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;
                $lines[] = "   \u{1F522} ".$qty." ".$unit." × ".format_brl($unitPrice)." = *".format_brl($lineTotal)."*";
            } else {
                $hasConsulta = true;
                $lines[] = "   \u{1F522} ".$qty." ".$unit." — *sob consulta*";
            }

            $lines[] = "   \u{1F517} ".route('products.show', $product);
            $lines[] = '';
        }

        $lines[] = '━━━━━━━━━━━━━━━';
        $subtotalLine = "\u{1F4B0} *Subtotal: ".format_brl($subtotal)."*";
        if ($hasConsulta) {
            $subtotalLine .= ' _(itens sob consulta não inclusos)_';
        }
        $lines[] = $subtotalLine;

        if (! empty($freight['options'])) {
            $lines[] = '';
            $freightTitle = "\u{1F69A} *FRETE*";
            if ($freightCep) {
                $freightTitle .= ' (CEP '.$freightCep.')';
            }
            $lines[] = $freightTitle;

            $totalLines = [];
            foreach ($freight['options'] as $option) {
                $icon = match ($option['method']) {
                    'retirada' => "\u{1F3EC}",
                    'entrega_propria' => "\u{1F69B}",
                    'transportadora' => "\u{1F4E6}",
                    default => "\u{1F4AC}",
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
                $lines[] = "\u{2705} *TOTAL COM ENTREGA*";
                array_push($lines, ...$totalLines);
            }
        }

        if (($data['payment_method'] ?? 'whatsapp') === 'boleto') {
            $lines[] = '';
            $lines[] = "\u{1F4B3} *Pagamento:* Boleto (ficha cadastral aprovada) — prazo a confirmar pela equipe.";
        }

        $lines[] = '';
        $lines[] = '━━━━━━━━━━━━━━━';
        if (! empty($data['name'])) {
            $lines[] = "\u{1F464} Nome: ".$data['name'];
        }
        if (! empty($data['city'])) {
            $lines[] = "\u{1F4CD} Cidade: ".$data['city'];
        }
        if (! empty($data['phone'])) {
            $lines[] = "\u{1F4F1} Contato: ".$data['phone'];
        }

        $lines[] = '';
        $lines[] = '_Valores finais confirmados pela nossa equipe._';

        return implode("\n", $lines);
    }
}
