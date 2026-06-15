@extends('layouts.store')

@section('title', ($product->seo_title ?: $product->name . ' em Curitiba') . ' | Radovanski Big Bags')
@section('meta_description', $product->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: $product->name), 155))
@section('og_type', 'product')
@if($product->coverImage())
    @section('og_image', asset('storage/' . $product->coverImage()->path))
@endif

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@type": "Product",
    "name": @json($product->name),
    "description": @json(\Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: ''), 300)),
    @if($product->coverImage())
    "image": @json(asset('storage/' . $product->coverImage()->path)),
    @endif
    "sku": @json($product->sku ?: (string) $product->id),
    "itemCondition": @json($product->condition === 'novo' ? 'https://schema.org/NewCondition' : 'https://schema.org/UsedCondition'),
    "brand": { "@type": "Brand", "name": "Radovanski Big Bags" },
    @if($product->price_visible && $product->price !== null)
    "offers": {
        "@type": "Offer",
        "price": @json((string) $product->price),
        "priceCurrency": "BRL",
        "availability": @json($product->availability === 'esgotado' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock'),
        "areaServed": "Curitiba"
    }
    @else
    "offers": {
        "@type": "Offer",
        "priceCurrency": "BRL",
        "availability": "https://schema.org/InStock",
        "areaServed": "Curitiba"
    }
    @endif
}
</script>
@endpush

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-brand-700">Início</a> ›
        <a href="{{ route('category', $product->category) }}" class="hover:text-brand-700">{{ $product->category->name }}</a> ›
        <span class="text-ink">{{ $product->name }}</span>
    </nav>

    <div class="grid lg:grid-cols-2 gap-8" x-data="{ mainImage: '{{ $product->coverImage() ? asset('storage/' . $product->coverImage()->path) : '' }}', qty: {{ $product->min_order_qty }}, variant: '' }">

        {{-- Galeria --}}
        <div>
            @if($product->images->isNotEmpty())
                <img :src="mainImage" src="{{ asset('storage/' . $product->coverImage()->path) }}"
                     alt="{{ $product->coverImage()->alt ?? $product->name }}"
                     class="w-full aspect-square object-cover rounded-xl bg-gray-100 border border-gray-200">
                @if($product->images->count() > 1)
                    <div class="flex gap-2 mt-3 overflow-x-auto">
                        @foreach($product->images as $image)
                            <button type="button" @click="mainImage = '{{ asset('storage/' . $image->path) }}'"
                                    class="shrink-0 border-2 rounded-lg overflow-hidden"
                                    :class="mainImage === '{{ asset('storage/' . $image->path) }}' ? 'border-brand-600' : 'border-transparent'">
                                <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->alt ?? $product->name }}"
                                     class="w-20 h-20 object-cover" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="w-full aspect-square rounded-xl bg-brand-50 flex items-center justify-center text-brand-300">
                    <svg class="w-24 h-24" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            @endif

            @if($product->video_url)
                @php
                    $videoId = null;
                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]{11})/', $product->video_url, $m)) {
                        $videoId = $m[1];
                    }
                @endphp
                @if($videoId)
                    <div class="mt-4 aspect-video rounded-xl overflow-hidden border border-gray-200">
                        <iframe class="w-full h-full" src="https://www.youtube-nocookie.com/embed/{{ $videoId }}"
                                title="Vídeo: {{ $product->name }}" loading="lazy" allowfullscreen></iframe>
                    </div>
                @endif
            @endif
        </div>

        {{-- Informações e ação --}}
        <div>
            <span class="text-xs font-bold uppercase rounded-full px-2.5 py-1
                {{ $product->condition === 'novo' ? 'bg-brand-100 text-brand-800' : '' }}
                {{ $product->condition === 'lavado' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ in_array($product->condition, ['sujo', 'usado']) ? 'bg-amber-100 text-amber-800' : '' }}">
                {{ $product->conditionLabel() }}
            </span>

            <h1 class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $product->name }}</h1>

            @if($product->short_description)
                <p class="text-gray-600 mt-2">{{ $product->short_description }}</p>
            @endif

            <p class="text-3xl font-extrabold text-brand-700 mt-4">{{ $product->displayPrice() }}
                @if($product->price_visible && $product->price !== null)
                    <span class="text-base font-semibold text-gray-500">/ {{ $product->unit }}</span>
                @endif
            </p>

            @if($product->price_visible && $product->quantityPrices->isNotEmpty())
                <div class="mt-3 border border-accent-400 bg-amber-50 rounded-lg p-3">
                    <p class="font-bold text-sm text-accent-600 mb-1">💰 Preço por quantidade</p>
                    <table class="text-sm w-full">
                        @foreach($product->quantityPrices as $bulk)
                            <tr>
                                <td class="py-0.5">A partir de {{ $bulk->min_qty }} un</td>
                                <td class="py-0.5 text-right font-bold">{{ format_brl($bulk->unit_price) }}/un</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            <form action="{{ route('cart.add') }}" method="post" class="mt-5 space-y-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if($product->variants->isNotEmpty())
                    <div>
                        <label class="font-bold text-sm block mb-1">Medida / variação</label>
                        <select name="variant_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5">
                            <option value="">Padrão @if($product->dimensionsLabel())({{ $product->dimensionsLabel() }})@endif</option>
                            @foreach($product->variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->name }}@if($product->price_visible && $variant->effectivePrice() !== null) — {{ format_brl($variant->effectivePrice()) }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-end gap-3">
                    <div>
                        <label class="font-bold text-sm block mb-1">Quantidade</label>
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button type="button" @click="qty = Math.max({{ $product->min_order_qty }}, qty - 1)" class="px-3.5 py-2.5 text-lg font-bold text-gray-500 hover:text-brand-700">−</button>
                            <input type="number" name="qty" x-model.number="qty" min="{{ $product->min_order_qty }}"
                                   class="w-16 text-center border-0 focus:ring-0 py-2.5 font-bold">
                            <button type="button" @click="qty++" class="px-3.5 py-2.5 text-lg font-bold text-gray-500 hover:text-brand-700">+</button>
                        </div>
                        @if($product->min_order_qty > 1)
                            <p class="text-xs text-gray-500 mt-1">Pedido mínimo: {{ $product->min_order_qty }} un</p>
                        @endif
                    </div>
                    <button class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg py-3 text-lg">
                        Adicionar ao pedido
                    </button>
                </div>
            </form>

            <a href="{{ store_whatsapp_link('Olá! Tenho interesse no produto: ' . $product->name . ' (' . url()->current() . ')') }}"
               target="_blank" rel="noopener"
               class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg py-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                Perguntar sobre este produto
            </a>

            {{-- Especificações --}}
            <div class="mt-6 border border-gray-200 rounded-xl overflow-hidden">
                <p class="bg-gray-50 px-4 py-2.5 font-bold text-sm uppercase text-gray-600">Especificações</p>
                <table class="w-full text-sm">
                    @if($product->dimensionsLabel())
                        <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Medidas (L × C × A)</td><td class="px-4 py-2 font-semibold">{{ $product->dimensionsLabel() }}</td></tr>
                    @endif
                    @if($product->capacity_kg)
                        <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Capacidade</td><td class="px-4 py-2 font-semibold">{{ number_format($product->capacity_kg, 0, ',', '.') }} kg</td></tr>
                    @endif
                    <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Condição</td><td class="px-4 py-2 font-semibold">{{ $product->conditionLabel() }}</td></tr>
                    @if($product->loops_count)
                        <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Alças</td><td class="px-4 py-2 font-semibold">{{ $product->loops_count }}</td></tr>
                    @endif
                    <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Válvula de descarga</td><td class="px-4 py-2 font-semibold">{{ $product->has_discharge_valve ? 'Sim' : 'Não' }}</td></tr>
                    @if($product->has_liner)
                        <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Liner interno</td><td class="px-4 py-2 font-semibold">Sim</td></tr>
                    @endif
                    @if($product->top_type)
                        <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-500">Parte superior</td><td class="px-4 py-2 font-semibold">{{ ['aberto' => 'Aberta (boca cheia)', 'valvula' => 'Válvula de enchimento', 'saia' => 'Saia'][$product->top_type] ?? $product->top_type }}</td></tr>
                    @endif
                    @foreach($product->attributeValues as $attributeValue)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2 text-gray-500">{{ $attributeValue->attribute->name }}</td>
                            <td class="px-4 py-2 font-semibold">{{ $attributeValue->value }}{{ $attributeValue->attribute->unit ? ' ' . $attributeValue->attribute->unit : '' }}</td>
                        </tr>
                    @endforeach
                    @if($product->useCases->isNotEmpty())
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2 text-gray-500">Indicado para</td>
                            <td class="px-4 py-2 font-semibold">{{ $product->useCases->pluck('name')->join(', ') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if($product->description)
        <div class="mt-10 max-w-3xl">
            <h2 class="text-xl font-extrabold mb-3">Descrição</h2>
            <div class="prose prose-sm max-w-none text-gray-700">{!! $product->description !!}</div>
        </div>
    @endif

    @if($related->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-extrabold mb-4">Você também pode precisar</h2>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($related as $relatedProduct)
                    @include('store._product-card', ['product' => $relatedProduct])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
