<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pedido {{ $quote->number }} — {{ store_setting('store_name', 'Radovanski Big Bags') }}</title>
    <meta property="og:title" content="Pedido {{ $quote->number }}">
    <meta property="og:description" content="Total: {{ format_brl($quote->total) }}. Toque para ver os detalhes e confirmar.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-ink antialiased py-6 px-3">

<div class="max-w-2xl mx-auto space-y-0">

    {{-- ===== CABEÇALHO ===== --}}
    <div class="bg-white rounded-t-2xl border border-gray-200 p-5 flex items-start gap-4">
        <img src="{{ store_setting('store_logo') ? asset('storage/'.store_setting('store_logo')) : asset('images/logo.svg') }}"
             alt="{{ store_setting('store_name') }}" class="h-16 w-16 object-contain shrink-0">
        <div class="flex-1 min-w-0">
            <p class="font-extrabold text-lg leading-tight text-brand-700">{{ store_setting('store_name', 'Radovanski Big Bags') }}</p>
            <p class="text-xs text-gray-500">{{ store_setting('store_address', '') }} — {{ store_setting('store_city', 'Curitiba - PR') }}</p>
            @if(store_setting('store_whatsapp'))
                <p class="text-xs text-gray-500">WhatsApp: {{ store_setting('store_whatsapp') }}</p>
            @endif
            @if(store_setting('store_cnpj'))
                <p class="text-xs text-gray-500">CNPJ: {{ store_setting('store_cnpj') }}</p>
            @endif
        </div>
        <div class="text-right shrink-0">
            <span class="bg-brand-700 text-white font-extrabold rounded-lg px-3 py-1 text-xs tracking-wide">PEDIDO</span>
            <p class="font-bold text-sm mt-1">{{ $quote->number }}</p>
            <p class="text-xs text-gray-500">{{ $quote->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- ===== FAIXA DE STATUS ===== --}}
    @if($quote->status === 'aprovado')
        <div class="bg-brand-600 text-white font-bold text-center py-2.5 text-sm border-x border-brand-700">
            ✓ Pedido confirmado em {{ $quote->approved_at?->format('d/m/Y \à\s H:i') }}
        </div>
    @elseif($quote->isExpired())
        <div class="bg-red-600 text-white font-bold text-center py-2.5 text-sm border-x border-red-700">
            Proposta expirada em {{ $quote->valid_until->format('d/m/Y') }} — fale conosco para renovar
        </div>
    @elseif($quote->valid_until)
        <div class="bg-ink text-white text-xs text-center py-2 border-x border-gray-800">
            Válido até <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>
        </div>
    @endif

    {{-- ===== CLIENTE ===== --}}
    <div class="bg-white border-x border-gray-200 px-5 py-4">
        <p class="text-xs font-bold uppercase text-gray-400 mb-1">Cliente</p>
        <p class="font-bold">{{ $quote->customer?->name }}@if($quote->customer?->company) — {{ $quote->customer->company }}@endif</p>
        @if($quote->customer?->document)
            <p class="text-sm text-gray-500">CNPJ/CPF: {{ $quote->customer->document }}</p>
        @endif
        @if($quote->customer?->phone)
            <p class="text-sm text-gray-500">WhatsApp: {{ $quote->customer->phone }}</p>
        @endif
        @if($quote->customer?->city)
            <p class="text-sm text-gray-500">{{ $quote->customer->city }}@if($quote->customer->state) — {{ $quote->customer->state }}@endif</p>
        @endif
    </div>

    {{-- ===== ITENS ===== --}}
    <div class="bg-white border-x border-gray-200 px-5 py-4">
        <p class="text-xs font-bold uppercase text-gray-400 mb-3">Itens do pedido</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink text-white text-xs">
                        <th class="text-left px-3 py-2.5 rounded-l-lg">#</th>
                        <th class="text-left px-2 py-2.5">Produto / Especificação</th>
                        <th class="text-center px-2 py-2.5">Qtde</th>
                        <th class="text-right px-2 py-2.5">Unit.</th>
                        <th class="text-right px-3 py-2.5 rounded-r-lg">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $i => $item)
                        <tr class="border-b border-gray-100 @if($loop->even) bg-gray-50 @endif">
                            <td class="px-3 py-2.5 text-gray-400 text-xs">{{ $loop->iteration }}</td>
                            <td class="px-2 py-2.5 font-medium">
                                {{ $item->description }}
                                @if($item->weight_kg)
                                    <span class="text-xs text-gray-400 block">{{ number_format($item->weight_kg, 1) }} kg/un.</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5 text-center font-bold">{{ $item->qty }}</td>
                            <td class="px-2 py-2.5 text-right text-gray-600 whitespace-nowrap">{{ format_brl($item->unit_price) }}</td>
                            <td class="px-3 py-2.5 text-right font-bold whitespace-nowrap">{{ format_brl($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($quote->total_weight_kg)
            <p class="text-xs text-gray-500 mt-2">Peso total estimado: <strong>{{ number_format($quote->total_weight_kg, 0) }} kg</strong>
                @if($quote->totalVolumeCbm()) · Volume: <strong>{{ number_format($quote->totalVolumeCbm(), 2) }} m³</strong>@endif
            </p>
        @endif
    </div>

    {{-- ===== RESUMO ===== --}}
    <div class="bg-white border-x border-gray-200 px-5 py-4">
        <p class="text-xs font-bold uppercase text-gray-400 mb-3">Resumo do pedido</p>
        <div class="ml-auto max-w-xs space-y-1.5 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Subtotal</span>
                <span class="font-semibold">{{ format_brl($quote->subtotal) }}</span>
            </div>
            @if($quote->discountAmount() > 0)
                <div class="flex justify-between text-green-700">
                    <span>Desconto</span>
                    <span class="font-semibold">− {{ format_brl($quote->discountAmount()) }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500">
                    {{ \App\Models\Quote::SHIPPING_METHODS[$quote->shipping_method] ?? 'Entrega' }}
                    @if($quote->shipping_carrier) ({{ $quote->shipping_carrier }})@endif
                </span>
                <span class="font-semibold">
                    @if($quote->shipping_method === 'retirada') Grátis
                    @else {{ format_brl($quote->shipping_cost) }}
                    @endif
                </span>
            </div>
            @if($quote->delivery_days)
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Prazo de entrega</span>
                    <span>{{ $quote->delivery_days }} {{ $quote->delivery_days === 1 ? 'dia útil' : 'dias úteis' }}</span>
                </div>
            @elseif($quote->shipping_deadline)
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Prazo</span><span>{{ $quote->shipping_deadline }}</span>
                </div>
            @endif
            <div class="flex justify-between items-baseline border-t-2 border-ink pt-2 mt-2">
                <span class="font-extrabold text-base">TOTAL GERAL</span>
                <span class="font-extrabold text-2xl text-brand-700">{{ format_brl($quote->total) }}</span>
            </div>
            @if($quote->payment_terms)
                <p class="text-right text-accent-600 font-bold text-sm">{{ $quote->payment_terms }}</p>
            @endif
        </div>
    </div>

    @if($quote->notes)
        <div class="bg-white border-x border-gray-200 px-5 py-4">
            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Observações</p>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
        </div>
    @endif

    {{-- ===== CONFIRMAR PEDIDO ===== --}}
    @if($quote->status !== 'aprovado' && !$quote->isExpired())
        @php
            $storePhone = preg_replace('/\D/', '', store_setting('store_whatsapp', ''));
            if ($storePhone && !str_starts_with($storePhone, '55')) { $storePhone = '55'.$storePhone; }
            $waMsg = rawurlencode('Olá! CONFIRMO o pedido '.$quote->number.' no valor de '.format_brl($quote->total).'. Como seguimos?');
        @endphp
        <div class="bg-brand-50 border-x border-b border-brand-100 px-5 py-5">
            <p class="font-extrabold text-lg mb-1">Confirmar pedido</p>
            <p class="text-sm text-gray-600 mb-4">Clique em <strong>CONFIRMAR AGORA</strong> para avisar nossa equipe pelo WhatsApp e fechar o pedido.</p>
            <div class="flex flex-col sm:flex-row items-center gap-5">
                <div class="flex-1 w-full space-y-2">
                    <form action="{{ route('quote.approve', $quote->public_token) }}" method="post">
                        @csrf
                        <button class="w-full flex items-center justify-center gap-2 bg-brand-700 hover:bg-brand-800 text-white font-extrabold rounded-xl px-6 py-3.5 text-base">
                            ✓ CONFIRMAR PEDIDO
                        </button>
                    </form>
                    <a href="https://wa.me/{{ $storePhone }}?text={{ $waMsg }}"
                       target="_blank" rel="noopener"
                       class="w-full flex items-center justify-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-xl px-6 py-3 text-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                        Confirmar pelo WhatsApp
                    </a>
                </div>
                <div class="text-center shrink-0">
                    <div class="bg-white p-2 rounded-lg border border-gray-200 inline-block">{!! qr_svg($quote->publicUrl(), 100) !!}</div>
                    <p class="text-xs text-gray-500 mt-1">Abrir em<br>outro aparelho</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== AÇÕES ===== --}}
    <div class="bg-white border-x border-b border-gray-200 px-5 py-5" x-data>
        <p class="font-bold text-sm mb-3">Ações</p>
        <div class="flex flex-wrap gap-2 text-sm font-semibold">

            <a href="{{ route('quote.pdf', $quote->public_token) }}"
               class="inline-flex items-center gap-1.5 border-2 border-ink rounded-lg px-4 py-2 hover:bg-gray-50">
                📄 Baixar PDF
            </a>

            <a href="{{ route('quote.repeat', $quote->public_token) }}"
               class="inline-flex items-center gap-1.5 border-2 border-brand-700 text-brand-700 rounded-lg px-4 py-2 hover:bg-brand-50">
                🔁 Repetir pedido
            </a>

            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-1.5 border-2 border-gray-300 text-gray-600 rounded-lg px-4 py-2 hover:bg-gray-50">
                🛍️ Novo pedido
            </a>

            @if(store_setting('store_whatsapp'))
                @php
                    $storePhone ??= preg_replace('/\D/', '', store_setting('store_whatsapp', ''));
                    if ($storePhone && !str_starts_with($storePhone, '55')) { $storePhone = '55'.$storePhone; }
                @endphp
                <a href="tel:{{ store_setting('store_whatsapp') }}"
                   class="inline-flex items-center gap-1.5 bg-gray-700 text-white rounded-lg px-4 py-2 hover:bg-gray-800">
                    📞 Ligar
                </a>
                <a href="https://wa.me/{{ $storePhone }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 bg-whatsapp text-white rounded-lg px-4 py-2 hover:bg-whatsapp-dark">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                    WhatsApp
                </a>
            @endif

            <a href="{{ route('credit-application.create') }}"
               class="inline-flex items-center gap-1.5 border-2 border-gray-300 text-gray-600 rounded-lg px-4 py-2 hover:bg-gray-50">
                🏢 Pagar com boleto
            </a>

            <button type="button"
                    @click="navigator.clipboard.writeText('{{ $quote->publicUrl() }}').then(() => $el.innerText = '✓ Copiado!')"
                    class="inline-flex items-center gap-1.5 border-2 border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                🔗 Copiar link
            </button>

            <button type="button"
                    @click="navigator.share ? navigator.share({ title: 'Pedido {{ $quote->number }}', url: '{{ $quote->publicUrl() }}' }) : null"
                    x-show="navigator.share"
                    class="inline-flex items-center gap-1.5 bg-brand-600 text-white rounded-lg px-4 py-2 hover:bg-brand-700">
                📤 Compartilhar
            </button>
        </div>
    </div>

    {{-- ===== GARANTIAS ===== --}}
    <div class="bg-white border border-gray-200 rounded-b-2xl px-5 py-4 grid sm:grid-cols-2 gap-2 text-sm">
        <div class="flex gap-2 items-center"><span class="text-brand-600 font-bold text-base">✓</span> Confirmação imediata via WhatsApp</div>
        <div class="flex gap-2 items-center"><span class="text-brand-600 font-bold text-base">✓</span> Pagamento na retirada ou entrega</div>
        <div class="flex gap-2 items-center"><span class="text-brand-600 font-bold text-base">✓</span> Retirada em {{ store_setting('store_city', 'Curitiba - PR') }}</div>
        <div class="flex gap-2 items-center"><span class="text-brand-600 font-bold text-base">✓</span> {{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</div>
    </div>

    {{-- ===== LOG DE E-MAILS ENVIADOS ===== --}}
    @if($quote->emailLogs->isNotEmpty())
        <div class="mt-4 bg-white rounded-2xl border border-gray-200 px-5 py-4">
            <p class="font-bold text-sm mb-3">E-mails enviados</p>
            <div class="space-y-2">
                @foreach($quote->emailLogs as $log)
                    <div class="flex items-center justify-between text-xs border border-gray-100 rounded-lg px-3 py-2">
                        <div>
                            <p class="font-semibold">{{ implode(', ', $log->to_recipients) }}</p>
                            <p class="text-gray-500">{{ $log->subject }} · {{ $log->sent_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full font-semibold
                            @if($log->status === 'aberto') bg-green-100 text-green-700
                            @elseif($log->status === 'falhou') bg-red-100 text-red-700
                            @else bg-blue-100 text-blue-700 @endif">
                            @if($log->status === 'aberto') 👁 Aberto
                            @elseif($log->status === 'falhou') ✗ Falhou
                            @else ✓ Enviado
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <p class="text-center text-xs text-gray-400 mt-4">
        {{ store_setting('store_name', 'Radovanski Big Bags') }} — Curitiba/PR
    </p>

</div>
</body>
</html>
