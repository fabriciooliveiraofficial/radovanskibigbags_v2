@extends('layouts.store')

@section('title', 'Produtos — Big Bags e Sacos de Ráfia em Curitiba | Radovanski Big Bags')
@section('meta_description', 'Catálogo completo de big bags novos, lavados e usados e sacos de ráfia em Curitiba. Filtre por medida, capacidade, condição e uso.')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-extrabold mb-1">Produtos</h1>
    <p class="text-gray-600 mb-5">{{ $products->total() }} {{ $products->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }}
        — <a href="{{ route('wizard') }}" class="text-accent-600 font-semibold hover:underline">não sabe a medida? Use o assistente</a></p>

    <div class="grid lg:grid-cols-[260px_1fr] gap-6" x-data="{ filtersOpen: false }">

        {{-- Filtros --}}
        <aside>
            <button type="button" class="lg:hidden w-full border-2 border-brand-600 text-brand-700 font-bold rounded-lg py-2.5 mb-3"
                    @click="filtersOpen = !filtersOpen">
                Filtros <span x-show="filtersOpen">▲</span><span x-show="!filtersOpen">▼</span>
            </button>

            <form method="get" action="{{ route('products.index') }}"
                  class="space-y-5 lg:block" x-show="filtersOpen || window.innerWidth >= 1024" x-cloak
                  :class="{ 'hidden': false }">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif

                <div>
                    <p class="font-bold text-sm uppercase text-gray-500 mb-2">Categoria</p>
                    <select name="categoria" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('categoria') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="font-bold text-sm uppercase text-gray-500 mb-2">Condição</p>
                    @foreach(\App\Models\Product::CONDITIONS as $value => $label)
                        <label class="flex items-center gap-2 text-sm py-0.5">
                            <input type="checkbox" name="condicao[]" value="{{ $value }}"
                                   @checked(in_array($value, (array) request('condicao', [])))
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div>
                    <p class="font-bold text-sm uppercase text-gray-500 mb-2">Capacidade</p>
                    <select name="capacidade" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Qualquer</option>
                        @foreach($capacityRanges as $key => $range)
                            <option value="{{ $key }}" @selected(request('capacidade') === $key)>{{ $range['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="font-bold text-sm uppercase text-gray-500 mb-2">Uso</p>
                    <select name="uso" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Qualquer</option>
                        @foreach($useCases as $useCase)
                            <option value="{{ $useCase->slug }}" @selected(request('uso') === $useCase->slug)>{{ $useCase->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="valvula" value="1" @checked(request('valvula') === '1')
                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Com válvula de descarga
                    </label>
                </div>

                <div>
                    <p class="font-bold text-sm uppercase text-gray-500 mb-2">Preço (R$)</p>
                    <div class="flex gap-2">
                        <input type="number" name="preco_min" value="{{ request('preco_min') }}" placeholder="mín"
                               class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm" min="0" step="0.01">
                        <input type="number" name="preco_max" value="{{ request('preco_max') }}" placeholder="máx"
                               class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm" min="0" step="0.01">
                    </div>
                </div>

                @foreach($filterableAttributes as $attribute)
                    @php($options = $attribute->values->pluck('value')->unique()->sort()->values())
                    @if($options->isNotEmpty())
                        <div>
                            <p class="font-bold text-sm uppercase text-gray-500 mb-2">{{ $attribute->name }}</p>
                            <div class="flex flex-wrap gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="attr[{{ $attribute->slug }}]" value=""
                                           @checked(!request('attr.' . $attribute->slug))
                                           class="peer sr-only">
                                    <span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600 hover:bg-gray-50 transition">
                                        Todos
                                    </span>
                                </label>
                                @foreach($options as $option)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="attr[{{ $attribute->slug }}]" value="{{ $option }}"
                                               @checked(request('attr.' . $attribute->slug) === $option)
                                               class="peer sr-only">
                                        <span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600 hover:bg-gray-50 transition">
                                            {{ $option }}{{ $attribute->unit ? ' ' . $attribute->unit : '' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="flex gap-2">
                    <button class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg py-2.5">Filtrar</button>
                    <a href="{{ route('products.index') }}" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Limpar</a>
                </div>
            </form>
        </aside>

        {{-- Lista de produtos --}}
        <div>
            <div class="flex justify-end mb-3">
                <form method="get">
                    @foreach(request()->except('ordenar', 'page') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $item)
                                <input type="hidden" name="{{ $key }}[{{ is_int($subKey) ? '' : $subKey }}]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <select name="ordenar" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="" @selected(!request('ordenar'))>Ordem padrão</option>
                        <option value="menor-preco" @selected(request('ordenar') === 'menor-preco')>Menor preço</option>
                        <option value="maior-preco" @selected(request('ordenar') === 'maior-preco')>Maior preço</option>
                        <option value="capacidade" @selected(request('ordenar') === 'capacidade')>Capacidade</option>
                    </select>
                </form>
            </div>

            @if($products->isEmpty())
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                    <p class="font-bold text-lg mb-1">Nenhum produto encontrado com esses filtros</p>
                    <p class="text-gray-600 text-sm mb-4">Fale com a gente — provavelmente temos o que você precisa.</p>
                    <a href="{{ store_whatsapp_link('Olá! Não encontrei o que procuro no site. Pode me ajudar?') }}" target="_blank" rel="noopener"
                       class="inline-block bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-5 py-2.5">
                        Perguntar no WhatsApp
                    </a>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($products as $product)
                        @include('store._product-card', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-6 pagination-brand">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
