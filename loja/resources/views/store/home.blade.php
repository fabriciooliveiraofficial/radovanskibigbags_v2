@extends('layouts.store')

@section('title', store_setting('seo_home_title', 'Big Bags em Curitiba — Novos, Lavados e Usados | Radovanski Big Bags'))

@section('content')
<div class="max-w-6xl mx-auto px-4">

    {{-- Apresentação direta, sem banner --}}
    <section class="py-8 sm:py-12 text-center">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-ink leading-tight">
            Big Bags em Curitiba<br>
            <span class="text-brand-700">novos, lavados e usados</span>
        </h1>
        <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
            Várias medidas em estoque para indústria, agronegócio, reciclagem, transporte e logística.
            Peça o orçamento pelo WhatsApp e retire no nosso depósito — pagamento na hora.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('products.index') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg px-6 py-3">
                Ver produtos
            </a>
            <a href="{{ route('wizard') }}" class="border-2 border-accent-500 text-accent-600 hover:bg-amber-50 font-bold rounded-lg px-6 py-3">
                🎯 Não sei a medida que preciso
            </a>
        </div>
    </section>

    {{-- Categorias (menu de restaurante: grandes e diretas) --}}
    @if($categories->isNotEmpty())
    <section class="py-6">
        <h2 class="text-xl font-extrabold mb-4">Escolha por categoria</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($categories as $category)
                <a href="{{ route('category', $category) }}"
                   class="border-2 border-gray-200 hover:border-brand-500 rounded-xl p-4 text-center transition bg-white">
                    @if($category->image_path)
                        <img src="{{ asset('storage/' . $category->image_path) }}" alt="{{ $category->name }}"
                             class="w-16 h-16 object-cover rounded-lg mx-auto mb-2" loading="lazy">
                    @endif
                    <span class="font-bold text-ink block">{{ $category->name }}</span>
                    <span class="text-xs text-gray-500">{{ $category->products_count }} {{ $category->products_count === 1 ? 'produto' : 'produtos' }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Usos (público-alvo) --}}
    @if($useCases->isNotEmpty())
    <section class="py-6">
        <h2 class="text-xl font-extrabold mb-4">Para que você precisa?</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($useCases as $useCase)
                <a href="{{ route('use-case', $useCase) }}"
                   class="border border-gray-300 hover:border-brand-500 hover:bg-brand-50 rounded-full px-4 py-2 text-sm font-semibold">
                    {{ $useCase->name }}
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Destaques --}}
    @if($featured->isNotEmpty())
    <section class="py-6">
        <h2 class="text-xl font-extrabold mb-4">Mais pedidos</h2>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($featured as $product)
                @include('store._product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
    @endif

    {{-- Como funciona --}}
    <section class="py-10">
        <h2 class="text-xl font-extrabold mb-6 text-center">Como funciona</h2>
        <div class="grid sm:grid-cols-3 gap-6 text-center">
            <div>
                <div class="w-12 h-12 rounded-full bg-brand-600 text-white font-extrabold text-xl flex items-center justify-center mx-auto mb-3">1</div>
                <p class="font-bold">Escolha os produtos</p>
                <p class="text-sm text-gray-600 mt-1">Navegue, use os filtros ou o assistente de medidas e monte sua lista.</p>
            </div>
            <div>
                <div class="w-12 h-12 rounded-full bg-brand-600 text-white font-extrabold text-xl flex items-center justify-center mx-auto mb-3">2</div>
                <p class="font-bold">Peça pelo WhatsApp</p>
                <p class="text-sm text-gray-600 mt-1">Sua lista vira uma mensagem pronta. Respondemos com o orçamento.</p>
            </div>
            <div>
                <div class="w-12 h-12 rounded-full bg-brand-600 text-white font-extrabold text-xl flex items-center justify-center mx-auto mb-3">3</div>
                <p class="font-bold">Retire e pague na hora</p>
                <p class="text-sm text-gray-600 mt-1">Retirada no depósito em Curitiba ou entrega combinada. Pagamento presencial.</p>
            </div>
        </div>
    </section>

    {{-- FAQ resumido --}}
    @if($faqs->isNotEmpty())
    <section class="py-6">
        <h2 class="text-xl font-extrabold mb-4">Perguntas frequentes</h2>
        <div class="space-y-2">
            @foreach($faqs as $faq)
                <details class="border border-gray-200 rounded-lg px-4 py-3 bg-white">
                    <summary class="font-semibold cursor-pointer">{{ $faq->question }}</summary>
                    <p class="text-gray-600 text-sm mt-2 whitespace-pre-line">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>
        <a href="{{ route('faq') }}" class="inline-block mt-3 text-brand-700 font-semibold hover:underline">Ver todas as dúvidas →</a>
    </section>
    @endif

</div>
@endsection
