{{-- Card de produto estilo "item de cardápio": foto à esquerda, dados, preço e ação --}}
@php($cover = $product->coverImage())
<div class="flex gap-4 border border-gray-200 rounded-xl p-4 hover:border-brand-400 hover:shadow-sm transition bg-white">
    <a href="{{ route('products.show', $product) }}" class="shrink-0">
        @if($cover)
            <img src="{{ asset('storage/' . $cover->path) }}" alt="{{ $cover->alt ?? $product->name }}"
                 class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg bg-gray-100" loading="lazy">
        @else
            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-lg bg-brand-50 flex items-center justify-center text-brand-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        @endif
    </a>

    <div class="flex-1 min-w-0 flex flex-col">
        <div class="flex items-start gap-2">
            <a href="{{ route('products.show', $product) }}" class="font-bold text-ink hover:text-brand-700 leading-snug">
                {{ $product->name }}
            </a>
            <span class="ml-auto shrink-0 text-xs font-bold uppercase rounded-full px-2 py-0.5
                {{ $product->condition === 'novo' ? 'bg-brand-100 text-brand-800' : '' }}
                {{ $product->condition === 'lavado' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ in_array($product->condition, ['sujo', 'usado']) ? 'bg-amber-100 text-amber-800' : '' }}">
                {{ $product->conditionLabel() }}
            </span>
        </div>

        <p class="text-sm text-gray-500 mt-0.5">
            @if($product->dimensionsLabel()){{ $product->dimensionsLabel() }}@endif
            @if($product->capacity_kg) · até {{ number_format($product->capacity_kg, 0, ',', '.') }} kg @endif
        </p>

        @if($product->short_description)
            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $product->short_description }}</p>
        @endif

        <div class="mt-auto pt-2 flex items-center gap-3 flex-wrap">
            <span class="font-extrabold text-lg {{ $product->price_visible && $product->price !== null ? 'text-brand-700' : 'text-gray-500 text-base' }}">
                {{ $product->displayPrice() }}
            </span>
            @if($product->price_visible && $product->lowestBulkPrice())
                <span class="text-xs text-accent-600 font-semibold">atacado: {{ format_brl($product->lowestBulkPrice()) }}/un</span>
            @endif

            <form action="{{ route('cart.add') }}" method="post" class="ml-auto">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" value="{{ $product->min_order_qty }}">
                <button class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg px-3.5 py-2">
                    + Pedido
                </button>
            </form>
        </div>
    </div>
</div>
