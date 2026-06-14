@extends('layouts.store')

@section('title', 'Qual big bag eu preciso? Assistente de medidas | Radovanski Big Bags Curitiba')
@section('meta_description', 'Não sabe qual medida de big bag precisa? Responda 3 perguntas e veja os modelos certos para o seu uso. Big bags em Curitiba com retirada no local.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-center">🎯 Qual big bag você precisa?</h1>
    <p class="text-gray-600 text-center mt-2 mb-8">Responda 3 perguntas rápidas e mostramos os modelos certos — sem precisar saber medidas.</p>

    <form method="get" class="space-y-8 bg-white border border-gray-200 rounded-2xl p-6">

        <div>
            <p class="font-bold text-lg mb-3">1. O que você vai armazenar ou transportar?</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($useCases as $useCase)
                    <label class="border-2 rounded-xl px-3 py-3 text-center cursor-pointer font-semibold text-sm transition
                                  has-checked:border-brand-600 has-checked:bg-brand-50 border-gray-200 hover:border-brand-300">
                        <input type="radio" name="uso" value="{{ $useCase->slug }}" class="sr-only" @checked(request('uso') === $useCase->slug)>
                        {{ $useCase->name }}
                    </label>
                @endforeach
                <label class="border-2 rounded-xl px-3 py-3 text-center cursor-pointer font-semibold text-sm transition
                              has-checked:border-brand-600 has-checked:bg-brand-50 border-gray-200 hover:border-brand-300">
                    <input type="radio" name="uso" value="" class="sr-only" @checked(request('uso') === '' || !request()->has('uso'))>
                    Outro / não sei
                </label>
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">2. Quanto peso por saco, aproximadamente?</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($capacityRanges as $key => $range)
                    <label class="border-2 rounded-xl px-3 py-3 text-center cursor-pointer font-semibold text-sm transition
                                  has-checked:border-brand-600 has-checked:bg-brand-50 border-gray-200 hover:border-brand-300">
                        <input type="radio" name="peso" value="{{ $key }}" class="sr-only" @checked(request('peso') === $key)>
                        {{ $range['label'] }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">3. Prefere novo ou econômico?</p>
            <div class="grid grid-cols-2 gap-2">
                <label class="border-2 rounded-xl px-3 py-3 text-center cursor-pointer font-semibold text-sm transition
                              has-checked:border-brand-600 has-checked:bg-brand-50 border-gray-200 hover:border-brand-300">
                    <input type="radio" name="condicao" value="novo" class="sr-only" @checked(request('condicao') === 'novo')>
                    Novo<br><span class="font-normal text-gray-500 text-xs">primeira utilização</span>
                </label>
                <label class="border-2 rounded-xl px-3 py-3 text-center cursor-pointer font-semibold text-sm transition
                              has-checked:border-brand-600 has-checked:bg-brand-50 border-gray-200 hover:border-brand-300">
                    <input type="radio" name="condicao" value="economico" class="sr-only" @checked(request('condicao') === 'economico')>
                    Econômico<br><span class="font-normal text-gray-500 text-xs">lavado ou usado, mais barato</span>
                </label>
            </div>
        </div>

        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl py-3.5 text-lg">
            Ver produtos recomendados
        </button>
    </form>

    @if($results !== null)
        <div class="mt-10">
            @if($results->isEmpty())
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center">
                    <p class="font-bold text-lg mb-1">Não achamos um modelo exato no estoque online</p>
                    <p class="text-gray-600 text-sm mb-4">Mas trabalhamos com muitas medidas — fale com a gente que resolvemos.</p>
                    <a href="{{ store_whatsapp_link('Olá! Usei o assistente do site e não encontrei o big bag ideal. Preciso de ajuda.') }}" target="_blank" rel="noopener"
                       class="inline-block bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-5 py-2.5">
                        Falar no WhatsApp
                    </a>
                </div>
            @else
                <h2 class="text-xl font-extrabold mb-4">✅ Recomendados para você ({{ $results->count() }})</h2>
                <div class="grid gap-3">
                    @foreach($results as $product)
                        @include('store._product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
