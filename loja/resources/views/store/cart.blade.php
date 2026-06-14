@extends('layouts.store')

@section('title', 'Minha cotação | Radovanski Big Bags Curitiba')
@section('meta_description', 'Revise sua lista de big bags e sacos de ráfia e peça o orçamento pelo WhatsApp.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold mb-1">Minha cotação</h1>
    <p class="text-gray-600 mb-6">Revise os itens e envie pelo WhatsApp — respondemos com o orçamento completo.</p>

    @if($items->isEmpty())
        <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
            <p class="font-bold text-lg mb-2">Sua lista está vazia</p>
            <a href="{{ route('products.index') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg px-6 py-3">
                Ver produtos
            </a>
        </div>
    @else
        @php($total = 0)
        @php($hasConsulta = false)
        <div class="space-y-3">
            @foreach($items as $item)
                @php($cover = $item['product']->coverImage())
                @php($lineTotal = $item['unit_price'] !== null ? $item['unit_price'] * $item['qty'] : null)
                @php($total += $lineTotal ?? 0)
                @php($hasConsulta = $hasConsulta || $lineTotal === null)
                <div class="flex gap-3 border border-gray-200 rounded-xl p-3 bg-white items-center">
                    @if($cover)
                        <img src="{{ asset('storage/' . $cover->path) }}" alt="{{ $item['product']->name }}" class="w-16 h-16 object-cover rounded-lg bg-gray-100">
                    @endif
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('products.show', $item['product']) }}" class="font-bold leading-snug hover:text-brand-700">{{ $item['product']->name }}</a>
                        @if($item['variant'])
                            <p class="text-sm text-gray-500">{{ $item['variant']->name }}</p>
                        @endif
                        <p class="text-sm font-semibold text-brand-700 mt-0.5">
                            {{ $item['unit_price'] !== null ? format_brl($item['unit_price']) . '/un' : 'Sob consulta' }}
                        </p>
                    </div>
                    <form action="{{ route('cart.update') }}" method="post" class="flex items-center gap-1">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                        <input type="hidden" name="variant_id" value="{{ $item['variant']?->id }}">
                        <input type="number" name="qty" value="{{ $item['qty'] }}" min="0"
                               class="w-16 border border-gray-300 rounded-lg px-2 py-1.5 text-center font-bold text-sm">
                        <button class="text-xs font-bold text-brand-700 hover:underline px-1">OK</button>
                    </form>
                    <form action="{{ route('cart.remove') }}" method="post">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                        <input type="hidden" name="variant_id" value="{{ $item['variant']?->id }}">
                        <button class="text-gray-400 hover:text-red-600 p-1" aria-label="Remover">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t-2 border-gray-100 pt-4 flex justify-between items-baseline">
            <span class="font-bold">Estimativa{{ $hasConsulta ? ' (itens sob consulta não inclusos)' : '' }}:</span>
            <span class="text-2xl font-extrabold text-brand-700">{{ format_brl($total) }}</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">O valor final (com frete/retirada e condições) é confirmado no orçamento enviado pela nossa equipe.</p>

        {{-- Frete / retirada --}}
        <div class="mt-6 border border-gray-200 rounded-2xl p-5">
            <p class="font-bold text-lg mb-1">🚚 Entrega ou retirada</p>
            <p class="text-sm text-gray-600 mb-3">Informe seu CEP para estimar a entrega — ou retire grátis no nosso depósito em Curitiba.</p>
            <form action="{{ route('cart.freight') }}" method="post" class="flex gap-2 max-w-xs">
                @csrf
                <input type="text" name="cep" value="{{ $freightCep ?? '' }}" placeholder="Seu CEP" inputmode="numeric"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5">
                <button class="bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg px-4">Calcular</button>
            </form>
            @error('cep')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror

            @if(!empty($freight['options']))
                <div class="mt-4 space-y-2">
                    @foreach($freight['options'] as $option)
                        <div class="flex justify-between items-center border border-gray-100 rounded-lg px-4 py-2.5 text-sm {{ $option['method'] === 'retirada' ? 'bg-brand-50 border-brand-200' : '' }}">
                            <div>
                                <p class="font-semibold">{{ $option['label'] }}</p>
                                @if($option['deadline'])
                                    <p class="text-xs text-gray-500">{{ $option['deadline'] }}</p>
                                @endif
                            </div>
                            <span class="font-bold {{ ($option['cost'] ?? null) === 0.0 ? 'text-brand-700' : '' }}">
                                {{ $option['cost'] === null ? 'Sob consulta' : ($option['cost'] == 0 ? 'Grátis' : format_brl($option['cost'])) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">Estimativa. O valor final é confirmado no orçamento.</p>
            @endif
        </div>

        <form action="{{ route('cart.whatsapp') }}" method="post" class="mt-6 bg-brand-50 border border-brand-200 rounded-2xl p-5 space-y-3">
            @csrf
            <p class="font-bold text-lg">Enviar pedido de orçamento</p>
            <div class="grid sm:grid-cols-3 gap-3">
                <input type="text" name="name" placeholder="Seu nome (opcional)" value="{{ old('name') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="tel" name="phone" placeholder="Seu WhatsApp (opcional)" value="{{ old('phone') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="city" placeholder="Sua cidade (opcional)" value="{{ old('city') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
            </div>
            <button class="w-full inline-flex items-center justify-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-xl py-3.5 text-lg">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                Pedir orçamento no WhatsApp
            </button>
            <p class="text-xs text-gray-500 text-center">Abre o WhatsApp com sua lista pronta. Sem cadastro, sem pagamento online.</p>
        </form>
    @endif
</div>
@endsection
