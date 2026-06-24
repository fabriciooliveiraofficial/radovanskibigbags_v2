<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Orçamento {{ $quote->number }} — {{ store_setting('store_name', 'Radovanski Big Bags') }}</title>
    <meta property="og:title" content="Orçamento {{ $quote->number }} — {{ store_setting('store_name', 'Radovanski Big Bags') }}">
    <meta property="og:description" content="Orçamento no valor de {{ format_brl($quote->total) }}. Toque para ver os detalhes e aprovar.">
    <meta property="og:image" content="{{ asset('images/logo.svg') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-ink antialiased py-6 px-3">

<div class="max-w-2xl mx-auto" id="orcamento">

    {{-- Cabeçalho --}}
    <div class="bg-white rounded-t-2xl border border-gray-200 p-6 flex items-center gap-4">
        <img src="{{ store_setting('store_logo') ? asset('storage/' . store_setting('store_logo')) : asset('images/logo.svg') }}"
             alt="{{ store_setting('store_name') }}" class="h-16 w-16">
        <div class="flex-1">
            <p class="font-extrabold text-xl leading-tight text-brand-700">{{ store_setting('store_name', 'Radovanski Big Bags') }}</p>
            <p class="text-sm text-gray-500">{{ store_setting('store_address', '') }} {{ store_setting('store_city', 'Curitiba - PR') }}</p>
            <p class="text-sm text-gray-500">WhatsApp: {{ store_setting('store_whatsapp', '') }}</p>
        </div>
        <div class="text-right">
            <p class="bg-accent-500 text-white font-extrabold rounded-lg px-3 py-1 text-sm">ORÇAMENTO</p>
            <p class="font-bold mt-1">{{ $quote->number }}</p>
            <p class="text-xs text-gray-500">{{ $quote->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Status / validade --}}
    @if($quote->status === 'aprovado')
        <div class="bg-brand-600 text-white font-bold text-center py-2.5 border-x border-brand-700">
            ✓ Orçamento aprovado em {{ $quote->approved_at?->format('d/m/Y H:i') }}
        </div>
    @elseif($quote->isExpired())
        <div class="bg-red-600 text-white font-bold text-center py-2.5 border-x border-red-700">
            Este orçamento expirou em {{ $quote->valid_until->format('d/m/Y') }} — solicite uma atualização pelo WhatsApp
        </div>
    @elseif($quote->valid_until)
        <div class="bg-ink text-white text-sm text-center py-2 border-x border-gray-800">
            Válido até <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>
        </div>
    @endif

    {{-- Cliente --}}
    <div class="bg-white border-x border-gray-200 px-6 py-4 flex flex-col md:flex-row gap-4 justify-between">
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Cliente</p>
            <p class="font-bold">{{ $quote->customer?->name }}@if($quote->customer?->company) — {{ $quote->customer->company }}@endif</p>
            @if($quote->customer?->document)
                <p class="text-sm text-gray-500">CNPJ/CPF: {{ $quote->customer->document }}</p>
            @endif
        </div>
        @if($quote->shipping_method !== 'retirada' && $quote->formatted_delivery_address)
            <div class="md:text-right">
                <p class="text-xs font-bold uppercase text-gray-400 mb-1">Endereço de Entrega</p>
                @if($quote->google_maps_link)
                    <a href="{{ $quote->google_maps_link }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline font-bold text-sm block">
                        {{ $quote->formatted_delivery_address }} ↗
                    </a>
                @else
                    <p class="text-sm text-gray-600 font-bold">{{ $quote->formatted_delivery_address }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Itens --}}
    <div class="bg-white border-x border-gray-200 px-6 py-4">
        <p class="text-xs font-bold uppercase text-gray-400 mb-2">Itens</p>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-ink text-white">
                    <th class="text-left px-3 py-2 rounded-l-lg">Descrição</th>
                    <th class="text-center px-2 py-2">Qtde</th>
                    <th class="text-right px-2 py-2">Unitário</th>
                    <th class="text-right px-2 py-2">Desconto</th>
                    <th class="text-right px-3 py-2 rounded-r-lg">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                    <tr class="border-b border-gray-100">
                        <td class="px-3 py-2.5">{{ $item->description }}</td>
                        <td class="px-2 py-2.5 text-center">{{ $item->qty }}</td>
                        <td class="px-2 py-2.5 text-right whitespace-nowrap">{{ format_brl($item->unit_price) }}</td>
                        <td class="px-2 py-2.5 text-right whitespace-nowrap text-red-600">
                            @if($item->discountAmount() > 0)
                                − {{ format_brl($item->discountAmount()) }}
                                @if($item->discount_type === 'percent')
                                    <span class="text-gray-400">({{ rtrim(rtrim(number_format($item->discount_value, 2, ',', '.'), '0'), ',') }}%)</span>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right font-semibold whitespace-nowrap">{{ format_brl($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Resumo --}}
    <div class="bg-white border-x border-gray-200 px-6 py-4">
        <div class="ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-semibold">{{ format_brl($quote->subtotal) }}</span></div>
            @if($quote->discountAmount() > 0)
                <div class="flex justify-between text-red-600"><span>Desconto</span><span>− {{ format_brl($quote->discountAmount()) }}</span></div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500">{{ \App\Models\Quote::SHIPPING_METHODS[$quote->shipping_method] ?? 'Frete' }}@if($quote->shipping_carrier) ({{ $quote->shipping_carrier }})@endif</span>
                <span class="font-semibold">{{ $quote->shipping_method === 'retirada' ? 'Grátis' : format_brl($quote->shipping_cost) }}</span>
            </div>
            @if($quote->shipping_deadline)
                <div class="flex justify-between text-gray-500"><span>Prazo</span><span>{{ $quote->shipping_deadline }}</span></div>
            @endif
            <div class="flex justify-between items-baseline border-t-2 border-ink pt-2 mt-2">
                <span class="font-extrabold">TOTAL</span>
                <span class="font-extrabold text-2xl text-brand-700">{{ format_brl($quote->total) }}</span>
            </div>
            @if($quote->payment_terms)
                <p class="text-right text-accent-600 font-bold">{{ $quote->payment_terms }}</p>
            @endif
        </div>
    </div>

    @if($quote->notes)
        <div class="bg-white border-x border-gray-200 px-6 py-4">
            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Observações</p>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
        </div>
    @endif

    {{-- Aprovação --}}
    @if($quote->status !== 'aprovado' && !$quote->isExpired())
        <div class="bg-brand-50 border border-brand-200 px-6 py-5">
            <div class="flex flex-col sm:flex-row items-center gap-5">
                <div class="flex-1 text-center sm:text-left">
                    <p class="font-extrabold text-lg">Aprovar orçamento</p>
                    <p class="text-sm text-gray-600 mb-3">Ao aprovar, você confirma pelo WhatsApp e agendamos a retirada/entrega.</p>
                    <form action="{{ route('quote.approve', $quote->public_token) }}" method="post">
                        @csrf
                        <button class="inline-flex items-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-extrabold rounded-xl px-8 py-3.5 text-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                            APROVAR AGORA
                        </button>
                    </form>
                </div>
                <div class="text-center">
                    <div class="bg-white p-2 rounded-lg border border-gray-200 inline-block">{!! qr_svg($quote->publicUrl(), 110) !!}</div>
                    <p class="text-xs text-gray-500 mt-1">Escaneie para abrir<br>em outro aparelho</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Anexos --}}
    @if($quote->attachments->isNotEmpty())
        <div class="bg-white border-x border-gray-200 px-6 py-4">
            <p class="text-xs font-bold uppercase text-gray-400 mb-3">Documentos / Anexos</p>
            <div class="space-y-2">
                @foreach($quote->attachments as $att)
                    <a href="{{ $att->publicUrl() }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 group transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center text-xl shrink-0">
                            {{ $att->typeIcon() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $att->label ?: $att->original_filename }}</p>
                            @if($att->label && $att->original_filename)
                                <p class="text-xs text-gray-400 truncate">{{ $att->original_filename }}</p>
                            @endif
                            @if($att->size_bytes)
                                <p class="text-xs text-gray-400">{{ $att->sizeFormatted() }}</p>
                            @endif
                        </div>
                        <span class="text-xs font-semibold text-brand-700 group-hover:underline shrink-0">Baixar ↓</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Garantias / informações --}}
    <div class="bg-white border border-gray-200 rounded-b-2xl px-6 py-4 grid sm:grid-cols-2 gap-3 text-sm">
        <div class="flex gap-2"><span class="text-brand-600 font-bold">✓</span> Confirmação imediata via WhatsApp</div>
        <div class="flex gap-2"><span class="text-brand-600 font-bold">✓</span> Pagamento presencial na retirada/entrega</div>
        <div class="flex gap-2"><span class="text-brand-600 font-bold">✓</span> Retirada em {{ store_setting('store_city', 'Curitiba - PR') }}</div>
        <div class="flex gap-2"><span class="text-brand-600 font-bold">✓</span> Atendimento: {{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</div>
    </div>

    {{-- Ações: PDF, imagem e compartilhamento --}}
    <div class="mt-4 bg-white rounded-2xl border border-gray-200 p-5" x-data>
        <p class="font-bold mb-3 text-center">Compartilhar / salvar este orçamento</p>
        <div class="flex flex-wrap justify-center gap-2 text-sm font-semibold">
            <a href="{{ route('quote.pdf', $quote->public_token) }}"
               class="border-2 border-ink rounded-lg px-4 py-2 hover:bg-gray-50">📄 Baixar PDF</a>

            <a href="https://wa.me/?text={{ rawurlencode('Orçamento ' . $quote->number . ' — ' . store_setting('store_name', 'Radovanski Big Bags') . ': ' . $quote->publicUrl()) }}"
               target="_blank" rel="noopener" class="bg-whatsapp text-white rounded-lg px-4 py-2 hover:bg-whatsapp-dark">WhatsApp</a>

            <a href="https://t.me/share/url?url={{ rawurlencode($quote->publicUrl()) }}&text={{ rawurlencode('Orçamento ' . $quote->number) }}"
               target="_blank" rel="noopener" class="bg-sky-500 text-white rounded-lg px-4 py-2 hover:bg-sky-600">Telegram</a>

            <a href="mailto:?subject={{ rawurlencode('Orçamento ' . $quote->number . ' — ' . store_setting('store_name', 'Radovanski Big Bags')) }}&body={{ rawurlencode('Segue o link do orçamento: ' . $quote->publicUrl()) }}"
               class="bg-gray-600 text-white rounded-lg px-4 py-2 hover:bg-gray-700">E-mail</a>

            <a href="https://www.facebook.com/dialog/send?link={{ rawurlencode($quote->publicUrl()) }}&app_id=0&redirect_uri={{ rawurlencode($quote->publicUrl()) }}"
               target="_blank" rel="noopener" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700">Messenger</a>

            <button type="button"
                    @click="navigator.clipboard.writeText('{{ $quote->publicUrl() }}').then(() => $el.innerText = '✓ Copiado!')"
                    class="border-2 border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">🔗 Copiar link</button>

            <button type="button"
                    @click="navigator.share ? navigator.share({ title: 'Orçamento {{ $quote->number }}', url: '{{ $quote->publicUrl() }}' }) : null"
                    x-show="navigator.share"
                    class="bg-brand-600 text-white rounded-lg px-4 py-2 hover:bg-brand-700">📤 Mais opções</button>
        </div>
        <p class="text-xs text-gray-400 text-center mt-3">"Mais opções" abre o menu do seu celular: Instagram Direct, SMS, e-mail e todos os apps instalados.</p>
    </div>

    <p class="text-center text-xs text-gray-400 mt-4">
        {{ store_setting('store_name', 'Radovanski Big Bags') }} — Curitiba/PR
    </p>
</div>

</body>
</html>
