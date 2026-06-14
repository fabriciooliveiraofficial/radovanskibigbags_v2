<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\UseCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function home(): View
    {
        return view('store.home', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->withCount('products')->get(),
            'featured' => Product::active()->where('is_featured', true)->with('images')->orderBy('sort_order')->take(8)->get(),
            'useCases' => UseCase::where('is_active', true)->orderBy('sort_order')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->take(4)->get(),
        ]);
    }

    public function products(Request $request): View
    {
        $products = $this->filteredQuery($request)
            ->with(['images', 'category', 'variants'])
            ->paginate(24)
            ->withQueryString();

        return view('store.products', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'useCases' => UseCase::where('is_active', true)->orderBy('sort_order')->get(),
            'filterableAttributes' => Attribute::where('is_filterable', true)
                ->with('values')
                ->orderBy('sort_order')
                ->get(),
            'capacityRanges' => self::CAPACITY_RANGES,
        ]);
    }

    public const CAPACITY_RANGES = [
        'ate-500' => ['label' => 'Até 500 kg', 'min' => 0, 'max' => 500],
        '500-1000' => ['label' => '500 a 1.000 kg', 'min' => 500, 'max' => 1000],
        '1000-1500' => ['label' => '1.000 a 1.500 kg', 'min' => 1000, 'max' => 1500],
        'acima-1500' => ['label' => 'Acima de 1.500 kg', 'min' => 1500, 'max' => null],
    ];

    private function filteredQuery(Request $request): Builder
    {
        $query = Product::active();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoria = $request->query('categoria')) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $categoria));
        }

        if ($condicoes = array_filter((array) $request->query('condicao', []))) {
            $query->whereIn('condition', $condicoes);
        }

        if ($uso = $request->query('uso')) {
            $query->whereHas('useCases', fn (Builder $q) => $q->where('slug', $uso));
        }

        if ($capacidade = $request->query('capacidade')) {
            $range = self::CAPACITY_RANGES[$capacidade] ?? null;
            if ($range) {
                $query->where(function (Builder $q) use ($range) {
                    $applyRange = function (Builder $sub, string $column) use ($range) {
                        $sub->where($column, '>=', $range['min']);
                        if ($range['max'] !== null) {
                            $sub->where($column, '<=', $range['max']);
                        }
                    };

                    $q->where(fn (Builder $sub) => $applyRange($sub, 'capacity_kg'))
                        ->orWhereHas('variants', fn (Builder $sub) => $applyRange($sub, 'capacity_kg'));
                });
            }
        }

        if ($request->query('valvula') === '1') {
            $query->where('has_discharge_valve', true);
        }

        if (($min = $request->query('preco_min')) !== null && $min !== '') {
            $query->where('price_visible', true)->where('price', '>=', (float) $min);
        }

        if (($max = $request->query('preco_max')) !== null && $max !== '') {
            $query->where('price_visible', true)->where('price', '<=', (float) $max);
        }

        // Atributos dinâmicos: ?attr[cor]=azul
        foreach ((array) $request->query('attr', []) as $slug => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query->whereHas('attributeValues', function (Builder $q) use ($slug, $value) {
                $q->where('value', $value)
                    ->whereHas('attribute', fn (Builder $a) => $a->where('slug', $slug));
            });
        }

        return match ($request->query('ordenar')) {
            'menor-preco' => $query->orderByRaw('price IS NULL, price asc'),
            'maior-preco' => $query->orderByRaw('price IS NULL, price desc'),
            'capacidade' => $query->orderByRaw('capacity_kg IS NULL, capacity_kg asc'),
            default => $query->orderBy('sort_order')->orderBy('name'),
        };
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'variants' => fn ($q) => $q->where('is_active', true), 'quantityPrices', 'useCases', 'category', 'attributeValues.attribute']);

        $related = Product::active()
            ->where('id', '!=', $product->id)
            ->where(fn (Builder $q) => $q
                ->where('category_id', $product->category_id)
                ->orWhereHas('useCases', fn (Builder $u) => $u->whereIn('use_cases.id', $product->useCases->pluck('id'))))
            ->with('images')
            ->take(4)
            ->get();

        return view('store.product', compact('product', 'related'));
    }

    public function wizard(Request $request): View
    {
        $results = null;

        if ($request->filled('peso') || $request->filled('uso') || $request->filled('condicao')) {
            $query = Product::active()->with(['images', 'category']);

            if ($uso = $request->query('uso')) {
                $query->whereHas('useCases', fn (Builder $q) => $q->where('slug', $uso));
            }

            if ($peso = $request->query('peso')) {
                $range = self::CAPACITY_RANGES[$peso] ?? null;
                if ($range) {
                    // Capacidade do bag deve comportar o peso informado
                    $minCapacity = $range['min'];
                    $query->where(fn (Builder $q) => $q
                        ->where('capacity_kg', '>=', $minCapacity)
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('capacity_kg', '>=', $minCapacity)));
                }
            }

            if ($condicao = $request->query('condicao')) {
                if ($condicao === 'economico') {
                    $query->whereIn('condition', ['lavado', 'sujo', 'usado']);
                } elseif ($condicao === 'novo') {
                    $query->where('condition', 'novo');
                }
            }

            $results = $query->take(12)->get();
        }

        return view('store.wizard', [
            'useCases' => UseCase::where('is_active', true)->orderBy('sort_order')->get(),
            'capacityRanges' => self::CAPACITY_RANGES,
            'results' => $results,
        ]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('store.category', [
            'category' => $category,
            'products' => $category->activeProducts()->with(['images', 'variants'])->paginate(24),
        ]);
    }

    public function useCase(UseCase $useCase): View
    {
        abort_unless($useCase->is_active, 404);

        return view('store.use-case', [
            'useCase' => $useCase,
            'products' => $useCase->products()->where('is_active', true)->with(['images', 'category'])->paginate(24),
        ]);
    }

    public function pickup(): View
    {
        return view('store.pickup');
    }

    public function faq(): View
    {
        return view('store.faq', [
            'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
