@extends('layouts.store')

@section('title', 'Retirada e pagamento | Radovanski Big Bags Curitiba')
@section('meta_description', 'Como retirar seus big bags em Curitiba: endereço, horários e formas de pagamento presencial da Radovanski Big Bags.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold">Retirada e pagamento</h1>
    <p class="text-gray-600 mt-2 mb-8">Sem frete, sem espera: você retira no nosso depósito e paga na hora.</p>

    <div class="space-y-5">
        <div class="border border-gray-200 rounded-2xl p-5">
            <p class="font-bold text-lg mb-1">📍 Endereço de retirada</p>
            <p class="text-gray-700">{{ store_setting('store_address', 'Endereço a configurar no painel administrativo') }}<br>{{ store_setting('store_city', 'Curitiba - PR') }}</p>
            @if(store_setting('store_address'))
                <a class="inline-block mt-2 text-brand-700 font-semibold hover:underline"
                   href="https://www.google.com/maps/search/?api=1&query={{ urlencode(store_setting('store_address') . ', ' . store_setting('store_city', 'Curitiba - PR')) }}"
                   target="_blank" rel="noopener">Abrir no Google Maps →</a>
            @endif
        </div>

        <div class="border border-gray-200 rounded-2xl p-5">
            <p class="font-bold text-lg mb-1">🕗 Horário de atendimento</p>
            <p class="text-gray-700">{{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</p>
        </div>

        @php($methods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get())
        <div class="border border-gray-200 rounded-2xl p-5">
            <p class="font-bold text-lg mb-2">💳 Formas de pagamento (presencial)</p>
            @if($methods->isEmpty())
                <p class="text-gray-700">PIX, dinheiro e cartão. Confirme as condições no orçamento.</p>
            @else
                <ul class="space-y-1">
                    @foreach($methods as $method)
                        <li class="flex gap-2 text-gray-700"><span class="text-brand-600 font-bold">✓</span> {{ $method->name }}@if($method->description) — <span class="text-gray-500">{{ $method->description }}</span>@endif</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if(store_setting('pickup_info'))
            <div class="border border-accent-400 bg-amber-50 rounded-2xl p-5">
                <p class="font-bold text-lg mb-1">ℹ️ Importante</p>
                <p class="text-gray-700 whitespace-pre-line">{{ store_setting('pickup_info') }}</p>
            </div>
        @endif

        <div class="border border-gray-200 rounded-2xl p-5">
            <p class="font-bold text-lg mb-1">🚚 Não pode retirar?</p>
            <p class="text-gray-700">Entregamos em Curitiba e região com valor calculado por distância, e despachamos por transportadora para outras cidades. Faça seu pedido com frete pelo WhatsApp.</p>
            <a href="{{ store_whatsapp_link('Olá! Quero um orçamento com entrega. Meu CEP é: ') }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 mt-3 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-5 py-2.5">
                Cotar com entrega
            </a>
        </div>
    </div>
</div>
@endsection
