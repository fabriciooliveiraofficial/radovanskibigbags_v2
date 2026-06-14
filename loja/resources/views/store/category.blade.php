@extends('layouts.store')

@section('title', ($category->seo_title ?: $category->name . ' em Curitiba — pronta entrega') . ' | Radovanski Big Bags')
@section('meta_description', $category->seo_description ?: ($category->name . ' em Curitiba com retirada no local e atendimento pelo WhatsApp. ' . \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 100)))

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-brand-700">Início</a> ›
        <span class="text-ink">{{ $category->name }}</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-extrabold">{{ $category->name }} em Curitiba</h1>
    @if($category->description)
        <p class="text-gray-600 mt-2 max-w-3xl">{{ $category->description }}</p>
    @endif

    <div class="mt-6">
        @if($products->isEmpty())
            <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                <p class="font-bold text-lg mb-1">Estoque desta categoria em atualização</p>
                <a href="{{ store_whatsapp_link('Olá! Procuro produtos da categoria ' . $category->name . '. O que vocês têm disponível?') }}" target="_blank" rel="noopener"
                   class="inline-block mt-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-5 py-2.5">
                    Consultar disponibilidade no WhatsApp
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
