<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UseCase;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Configurações da loja ────────────────────────────────────────
        $settings = [
            'store_name' => 'Radovanski Big Bags',
            'store_city' => 'Curitiba - PR',
            'store_whatsapp' => '41999999999',
            'store_hours' => 'Seg a Sex 8h às 18h · Sáb 8h às 12h',
            'shipping_delivery_days' => '1 a 2 dias úteis',
            'pickup_info' => "Retirada com agendamento prévio pelo WhatsApp.\nTraga veículo adequado ao volume — ajudamos no carregamento.",
            'seo_home_title' => 'Big Bags em Curitiba — Novos, Lavados e Usados | Radovanski Big Bags',
            'seo_home_description' => 'Big bags novos, lavados e usados e sacos de ráfia em Curitiba. Várias medidas em estoque, orçamento pelo WhatsApp, retirada no local e entrega na região.',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        // ── Formas de pagamento ──────────────────────────────────────────
        $methods = [
            ['name' => 'PIX na retirada', 'description' => 'Pagamento instantâneo no balcão', 'sort_order' => 1],
            ['name' => 'Dinheiro', 'description' => 'Pagamento na retirada ou entrega', 'sort_order' => 2],
            ['name' => 'Cartão de débito', 'description' => null, 'sort_order' => 3],
            ['name' => 'Cartão de crédito até 10x', 'description' => 'Parcelamento sujeito a taxas da operadora', 'sort_order' => 4],
            ['name' => 'Boleto faturado (clientes cadastrados)', 'description' => 'Para empresas com cadastro aprovado', 'sort_order' => 5],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(['name' => $method['name']], $method);
        }

        // ── Usos recomendados ────────────────────────────────────────────
        $useCases = collect([
            ['name' => 'Grãos e Agronegócio', 'slug' => 'graos-e-agronegocio', 'description' => 'Big bags para soja, milho, trigo, ração, sementes e insumos agrícolas. Modelos com válvula de enchimento e descarga que agilizam o manuseio na propriedade.', 'sort_order' => 1],
            ['name' => 'Reciclagem', 'slug' => 'reciclagem', 'description' => 'Big bags usados e lavados com ótimo custo-benefício para coleta e armazenagem de plástico, papelão, alumínio e outros recicláveis.', 'sort_order' => 2],
            ['name' => 'Entulho e Construção', 'slug' => 'entulho-e-construcao', 'description' => 'Sacos resistentes para remoção de entulho, armazenagem de areia, brita e argamassa em obras.', 'sort_order' => 3],
            ['name' => 'Areia e Mineração', 'slug' => 'areia-e-mineracao', 'description' => 'Modelos reforçados de alta capacidade para areia, pedras e minérios.', 'sort_order' => 4],
            ['name' => 'Indústria', 'slug' => 'industria', 'description' => 'Big bags novos para produtos químicos, pellets, resinas e matérias-primas industriais, com opção de liner interno.', 'sort_order' => 5],
            ['name' => 'Transporte e Logística', 'slug' => 'transporte-e-logistica', 'description' => 'Unitização de cargas com alças reforçadas para movimentação com empilhadeira e paleteira.', 'sort_order' => 6],
        ])->map(fn (array $data) => UseCase::firstOrCreate(['slug' => $data['slug']], $data));

        // ── Categorias ───────────────────────────────────────────────────
        $categories = collect([
            ['name' => 'Big Bags Novos', 'slug' => 'big-bags-novos', 'description' => 'Big bags de primeira utilização, ideais para produtos que exigem higiene e apresentação: grãos, alimentos, químicos e indústria em geral.', 'sort_order' => 1],
            ['name' => 'Big Bags Lavados', 'slug' => 'big-bags-lavados', 'description' => 'Big bags usados que passaram por lavagem e inspeção. Economia de até 60% em relação ao novo, mantendo a resistência.', 'sort_order' => 2],
            ['name' => 'Big Bags Sujos', 'slug' => 'big-bags-sujos', 'description' => 'A opção mais econômica: big bags usados sem lavagem, perfeitos para entulho, reciclagem e usos onde a aparência não importa.', 'sort_order' => 3],
            ['name' => 'Sacos de Ráfia Novos', 'slug' => 'sacos-de-rafia-novos', 'description' => 'Sacos de ráfia novos em diversas medidas para grãos, farinhas, rações e embalagem em geral.', 'sort_order' => 4],
            ['name' => 'Sacos de Ráfia Usados', 'slug' => 'sacos-de-rafia-usados', 'description' => 'Sacos de ráfia de segunda mão selecionados, ideais para reciclagem e usos gerais com máximo de economia.', 'sort_order' => 5],
        ])->map(fn (array $data) => Category::firstOrCreate(['slug' => $data['slug']], $data))->keyBy('slug');

        // ── Produtos ─────────────────────────────────────────────────────
        $products = [
            [
                'category' => 'big-bags-novos',
                'name' => 'Big Bag Novo 90×90×120 — 1.000 kg',
                'slug' => 'big-bag-novo-90x90x120-1000kg',
                'condition' => 'novo',
                'short_description' => 'O tamanho mais usado do mercado: 1 m³, capacidade de 1.000 kg, 4 alças reforçadas. Pronta entrega em Curitiba.',
                'price' => 42.90, 'price_visible' => true,
                'capacity_kg' => 1000, 'width_cm' => 90, 'depth_cm' => 90, 'height_cm' => 120,
                'weight_kg' => 1.8, 'loops_count' => 4,
                'top_type' => 'aberto', 'bottom_type' => 'fechado',
                'is_featured' => true, 'stock_quantity' => 500, 'sort_order' => 1,
                'uses' => ['graos-e-agronegocio', 'industria', 'transporte-e-logistica'],
                'bulk' => [['min_qty' => 50, 'unit_price' => 39.90], ['min_qty' => 200, 'unit_price' => 36.90]],
            ],
            [
                'category' => 'big-bags-novos',
                'name' => 'Big Bag Novo 90×90×150 com Válvula — 1.200 kg',
                'slug' => 'big-bag-novo-90x90x150-valvula-1200kg',
                'condition' => 'novo',
                'short_description' => 'Modelo alto com válvula de descarga no fundo: descarregue sem virar o saco. Ideal para grãos e produtos a granel.',
                'price' => 54.90, 'price_visible' => true,
                'capacity_kg' => 1200, 'width_cm' => 90, 'depth_cm' => 90, 'height_cm' => 150,
                'weight_kg' => 2.2, 'loops_count' => 4, 'has_discharge_valve' => true,
                'top_type' => 'valvula', 'bottom_type' => 'valvula',
                'is_featured' => true, 'stock_quantity' => 300, 'sort_order' => 2,
                'uses' => ['graos-e-agronegocio', 'industria'],
                'bulk' => [['min_qty' => 50, 'unit_price' => 51.90]],
            ],
            [
                'category' => 'big-bags-novos',
                'name' => 'Big Bag Novo com Liner Interno — 1.000 kg',
                'slug' => 'big-bag-novo-liner-1000kg',
                'condition' => 'novo',
                'short_description' => 'Com saco plástico interno (liner) para produtos finos, úmidos ou que exigem proteção extra contra umidade.',
                'price' => 62.90, 'price_visible' => true,
                'capacity_kg' => 1000, 'width_cm' => 90, 'depth_cm' => 90, 'height_cm' => 120,
                'weight_kg' => 2.4, 'loops_count' => 4, 'has_liner' => true,
                'top_type' => 'saia', 'bottom_type' => 'fechado',
                'stock_quantity' => 150, 'sort_order' => 3,
                'uses' => ['industria', 'graos-e-agronegocio'],
            ],
            [
                'category' => 'big-bags-lavados',
                'name' => 'Big Bag Lavado 90×90×120 — 1.000 kg',
                'slug' => 'big-bag-lavado-90x90x120-1000kg',
                'condition' => 'lavado',
                'short_description' => 'Usado uma vez, lavado e inspecionado. A melhor relação custo-benefício para reciclagem e armazenagem geral.',
                'price' => 22.90, 'price_visible' => true,
                'capacity_kg' => 1000, 'width_cm' => 90, 'depth_cm' => 90, 'height_cm' => 120,
                'weight_kg' => 1.8, 'loops_count' => 4,
                'top_type' => 'aberto', 'bottom_type' => 'fechado',
                'is_featured' => true, 'stock_quantity' => 800, 'sort_order' => 4,
                'uses' => ['reciclagem', 'entulho-e-construcao', 'transporte-e-logistica'],
                'bulk' => [['min_qty' => 100, 'unit_price' => 19.90]],
            ],
            [
                'category' => 'big-bags-lavados',
                'name' => 'Big Bag Lavado Reforçado — 1.500 kg',
                'slug' => 'big-bag-lavado-reforcado-1500kg',
                'condition' => 'lavado',
                'short_description' => 'Modelo de alta capacidade lavado, para cargas pesadas como areia, brita e minérios.',
                'price' => 29.90, 'price_visible' => true,
                'capacity_kg' => 1500, 'width_cm' => 95, 'depth_cm' => 95, 'height_cm' => 130,
                'weight_kg' => 2.6, 'loops_count' => 4,
                'top_type' => 'aberto', 'bottom_type' => 'fechado',
                'stock_quantity' => 250, 'sort_order' => 5,
                'uses' => ['areia-e-mineracao', 'entulho-e-construcao'],
            ],
            [
                'category' => 'big-bags-sujos',
                'name' => 'Big Bag Sujo (sem lavagem) — 1.000 kg',
                'slug' => 'big-bag-sujo-1000kg',
                'condition' => 'sujo',
                'short_description' => 'A opção mais barata do estoque. Estrutura íntegra, sem lavagem. Perfeito para entulho e reciclagem.',
                'price' => 12.90, 'price_visible' => true,
                'capacity_kg' => 1000, 'width_cm' => 90, 'depth_cm' => 90, 'height_cm' => 120,
                'weight_kg' => 1.8, 'loops_count' => 4,
                'top_type' => 'aberto', 'bottom_type' => 'fechado',
                'is_featured' => true, 'stock_quantity' => 1200, 'sort_order' => 6,
                'uses' => ['reciclagem', 'entulho-e-construcao'],
                'bulk' => [['min_qty' => 100, 'unit_price' => 10.90], ['min_qty' => 500, 'unit_price' => 9.50]],
            ],
            [
                'category' => 'big-bags-sujos',
                'name' => 'Big Bag Sujo Grande — 1.500 kg',
                'slug' => 'big-bag-sujo-grande-1500kg',
                'condition' => 'sujo',
                'short_description' => 'Capacidade extra com preço de usado. Sob inspeção visual, sem lavagem.',
                'price' => null, 'price_visible' => false,
                'capacity_kg' => 1500, 'width_cm' => 95, 'depth_cm' => 95, 'height_cm' => 140,
                'weight_kg' => 2.6, 'loops_count' => 4,
                'availability' => 'sob_consulta', 'sort_order' => 7,
                'uses' => ['areia-e-mineracao', 'entulho-e-construcao', 'reciclagem'],
            ],
            [
                'category' => 'sacos-de-rafia-novos',
                'name' => 'Saco de Ráfia Novo 50×80 cm — 30 kg',
                'slug' => 'saco-rafia-novo-50x80-30kg',
                'condition' => 'novo',
                'short_description' => 'Saco de ráfia branco novo, ideal para grãos, farinhas e rações. Vendido em fardos de 100 unidades.',
                'price' => 1.45, 'price_visible' => true, 'min_order_qty' => 100, 'unit' => 'un',
                'capacity_kg' => 30, 'width_cm' => 50, 'height_cm' => 80,
                'weight_kg' => 0.08,
                'stock_quantity' => 20000, 'sort_order' => 8,
                'uses' => ['graos-e-agronegocio', 'industria'],
                'bulk' => [['min_qty' => 1000, 'unit_price' => 1.25], ['min_qty' => 5000, 'unit_price' => 1.10]],
                'variants' => [
                    ['name' => '45 × 75 cm — 25 kg', 'capacity_kg' => 25, 'width_cm' => 45, 'height_cm' => 75, 'price' => 1.30],
                    ['name' => '60 × 90 cm — 50 kg', 'capacity_kg' => 50, 'width_cm' => 60, 'height_cm' => 90, 'price' => 1.85],
                ],
            ],
            [
                'category' => 'sacos-de-rafia-usados',
                'name' => 'Saco de Ráfia Usado 50×80 cm',
                'slug' => 'saco-rafia-usado-50x80',
                'condition' => 'usado',
                'short_description' => 'Sacos usados selecionados, sem furos. O mais barato para reciclagem e usos gerais.',
                'price' => 0.60, 'price_visible' => true, 'min_order_qty' => 200, 'unit' => 'un',
                'capacity_kg' => 30, 'width_cm' => 50, 'height_cm' => 80,
                'weight_kg' => 0.08,
                'stock_quantity' => 50000, 'sort_order' => 9,
                'uses' => ['reciclagem', 'entulho-e-construcao'],
                'bulk' => [['min_qty' => 1000, 'unit_price' => 0.50]],
            ],
        ];

        foreach ($products as $data) {
            $category = $categories[$data['category']];
            $uses = $data['uses'] ?? [];
            $bulk = $data['bulk'] ?? [];
            $variants = $data['variants'] ?? [];
            unset($data['category'], $data['uses'], $data['bulk'], $data['variants']);

            $product = Product::firstOrCreate(
                ['slug' => $data['slug']],
                [...$data, 'category_id' => $category->id]
            );

            $product->useCases()->sync(
                $useCases->whereIn('slug', $uses)->pluck('id')
            );

            foreach ($bulk as $tier) {
                $product->quantityPrices()->firstOrCreate(['min_qty' => $tier['min_qty']], $tier);
            }

            foreach ($variants as $i => $variant) {
                $product->variants()->firstOrCreate(['name' => $variant['name']], [...$variant, 'sort_order' => $i]);
            }
        }

        // ── FAQ ──────────────────────────────────────────────────────────
        $faqs = [
            ['question' => 'Qual a diferença entre big bag novo, lavado e sujo?', 'answer' => "Novo: nunca utilizado, indicado para alimentos, químicos e produtos que exigem higiene.\nLavado: usado uma vez, lavado e inspecionado — ótimo custo-benefício.\nSujo: usado sem lavagem, a opção mais barata, ideal para entulho e reciclagem.", 'sort_order' => 1],
            ['question' => 'Qual medida de big bag preciso para 1.000 kg?', 'answer' => 'O modelo mais comum é o 90×90×120 cm, que comporta até 1.000 kg (1 m³). Se o material for leve e volumoso (como recicláveis), prefira modelos mais altos, de 150 cm. Use nosso assistente de medidas no site ou pergunte no WhatsApp.', 'sort_order' => 2],
            ['question' => 'Vocês entregam ou só retirada?', 'answer' => 'As duas opções: retirada gratuita no nosso depósito em Curitiba (com agendamento) ou entrega na região calculada por distância. Para outras cidades, despachamos por transportadora.', 'sort_order' => 3],
            ['question' => 'Quais formas de pagamento vocês aceitam?', 'answer' => 'PIX, dinheiro, cartão de débito e crédito (até 10x). Para empresas com cadastro, também trabalhamos com boleto faturado. O pagamento é feito na retirada ou entrega — não há cobrança pelo site.', 'sort_order' => 4],
            ['question' => 'Tem desconto para grandes quantidades?', 'answer' => 'Sim! Nossos preços caem conforme a quantidade — as faixas de atacado aparecem em cada produto. Para volumes muito grandes ou compra recorrente, fale com a gente no WhatsApp para condições especiais.', 'sort_order' => 5],
            ['question' => 'Vocês compram big bags usados?', 'answer' => 'Sim, compramos big bags e sacos de ráfia usados em bom estado na região de Curitiba. Envie fotos e quantidade pelo WhatsApp para avaliarmos.', 'sort_order' => 6],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq);
        }
    }
}
