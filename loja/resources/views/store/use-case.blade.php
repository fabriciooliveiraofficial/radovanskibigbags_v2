@extends('layouts.store')

@section('title', ($useCase->seo_title ?: 'Big Bags para ' . $useCase->name . ' em Curitiba') . ' | Radovanski Big Bags')
@section('meta_description', $useCase->seo_description ?: ('Big bags para ' . mb_strtolower($useCase->name) . ' em Curitiba. Novos, lavados e usados, várias medidas, retirada no local e orçamento pelo WhatsApp.'))

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-brand-700">Início</a> ›
        <span class="text-ink">Big bags para {{ mb_strtolower($useCase->name) }}</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-extrabold">Big Bags para {{ $useCase->name }} em Curitiba</h1>
    @if($useCase->description)
        <p class="text-gray-600 mt-2 max-w-3xl whitespace-pre-line">{{ $useCase->description }}</p>
    @endif

    <div class="mt-6">
        @if($products->isEmpty())
            <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                <p class="font-bold text-lg mb-1">Fale com a gente sobre {{ mb_strtolower($useCase->name) }}</p>
                <p class="text-gray-600 text-sm mb-3">Temos modelos sob consulta para esta aplicação.</p>
                <a href="{{ store_whatsapp_link('Olá! Preciso de big bags para ' . mb_strtolower($useCase->name) . '. O que vocês recomendam?') }}" target="_blank" rel="noopener"
                   class="inline-block bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-5 py-2.5">
                    Pedir recomendação no WhatsApp
                </a>
            </div>
        @else
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($products as $product)
                    @include('store._product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-6 pagination-brand">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
