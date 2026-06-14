<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\UseCase;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('products.index'), 'priority' => '0.9'],
            ['loc' => route('wizard'), 'priority' => '0.6'],
            ['loc' => route('pickup'), 'priority' => '0.5'],
            ['loc' => route('faq'), 'priority' => '0.6'],
        ]);

        Category::where('is_active', true)->get()->each(function (Category $category) use (&$urls) {
            $urls->push([
                'loc' => route('category', $category),
                'priority' => '0.9',
                'lastmod' => $category->updated_at?->toAtomString(),
            ]);
        });

        UseCase::where('is_active', true)->get()->each(function (UseCase $useCase) use (&$urls) {
            $urls->push([
                'loc' => route('use-case', $useCase),
                'priority' => '0.8',
                'lastmod' => $useCase->updated_at?->toAtomString(),
            ]);
        });

        Product::active()->get()->each(function (Product $product) use (&$urls) {
            $urls->push([
                'loc' => route('products.show', $product),
                'priority' => '0.8',
                'lastmod' => $product->updated_at?->toAtomString(),
            ]);
        });

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
