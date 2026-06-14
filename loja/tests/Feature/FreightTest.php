<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Shipping\FreightCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FreightTest extends TestCase
{
    use RefreshDatabase;

    private function configureOrigin(): void
    {
        Setting::set('shipping_origin_cep', '81000000');
        Setting::set('shipping_price_per_km', '4.50');
        Setting::set('shipping_min_fee', '25.00');
        Setting::set('shipping_max_radius_km', '60');
        Setting::set('shipping_delivery_days', '1 a 2 dias úteis');
    }

    public function test_pickup_is_always_available_even_with_all_apis_down(): void
    {
        Http::fake(fn () => Http::response(null, 500));

        $result = app(FreightCalculator::class)->quote('80000-000', [['weight_kg' => 1.8, 'qty' => 10]]);

        $this->assertSame('retirada', $result['options'][0]['method']);
        $this->assertSame(0.0, $result['options'][0]['cost']);
        $this->assertTrue($result['fallback']);
        $this->assertSame('sob_consulta', $result['options'][1]['method']);
    }

    public function test_own_delivery_calculated_by_distance_with_brasilapi_coordinates(): void
    {
        $this->configureOrigin();

        Http::fake([
            'brasilapi.com.br/api/cep/v2/81000000' => Http::response([
                'city' => 'Curitiba',
                'location' => ['coordinates' => ['latitude' => '-25.50', 'longitude' => '-49.29']],
            ]),
            'brasilapi.com.br/api/cep/v2/80000000' => Http::response([
                'city' => 'Curitiba',
                'location' => ['coordinates' => ['latitude' => '-25.43', 'longitude' => '-49.27']],
            ]),
            '*' => Http::response(null, 500),
        ]);

        $result = app(FreightCalculator::class)->quote('80000-000', [['weight_kg' => 1.8, 'qty' => 50]]);

        $delivery = collect($result['options'])->firstWhere('method', 'entrega_propria');

        $this->assertNotNull($delivery, 'Entrega própria deveria estar disponível dentro do raio.');
        // ~8 km haversine × 1,3 ≈ 10 km × R$ 4,50 ≈ R$ 45 (acima do mínimo de R$ 25)
        $this->assertGreaterThan(25, $delivery['cost']);
        $this->assertLessThan(100, $delivery['cost']);
        $this->assertSame('1 a 2 dias úteis', $delivery['deadline']);
    }

    public function test_own_delivery_skipped_outside_radius(): void
    {
        $this->configureOrigin();
        Setting::set('shipping_max_radius_km', '5');

        Http::fake([
            'brasilapi.com.br/api/cep/v2/81000000' => Http::response([
                'city' => 'Curitiba',
                'location' => ['coordinates' => ['latitude' => '-25.50', 'longitude' => '-49.29']],
            ]),
            'brasilapi.com.br/api/cep/v2/89000000' => Http::response([
                'city' => 'Blumenau',
                'location' => ['coordinates' => ['latitude' => '-26.91', 'longitude' => '-49.06']],
            ]),
            '*' => Http::response(null, 500),
        ]);

        $result = app(FreightCalculator::class)->quote('89000-000', [['weight_kg' => 1.8, 'qty' => 10]]);

        $this->assertNull(collect($result['options'])->firstWhere('method', 'entrega_propria'));
    }

    public function test_carrier_fallback_chain_melhorenvio_down_superfrete_responds(): void
    {
        $this->configureOrigin();
        Setting::set('melhorenvio_token', 'token-me');
        Setting::set('superfrete_token', 'token-sf');

        Http::fake([
            'melhorenvio.com.br/*' => Http::response(null, 500),
            'api.superfrete.com/*' => Http::response([
                ['name' => 'SEDEX', 'price' => 89.90, 'delivery_time' => 3, 'has_error' => false],
                ['name' => 'PAC', 'price' => 45.50, 'delivery_time' => 7, 'has_error' => false],
            ]),
            '*' => Http::response(null, 500),
        ]);

        $result = app(FreightCalculator::class)->quote('89000-000', [['weight_kg' => 1.8, 'qty' => 10]]);

        $carrier = collect($result['options'])->firstWhere('method', 'transportadora');

        $this->assertNotNull($carrier, 'SuperFrete deveria assumir quando Melhor Envio falha.');
        $this->assertSame(45.50, $carrier['cost']); // escolhe a opção mais barata
        $this->assertSame('PAC', $carrier['carrier']);
    }

    public function test_cart_freight_endpoint_stores_result_in_session(): void
    {
        Http::fake(fn () => Http::response(null, 500));

        $this->post('/cotacao/frete', ['cep' => '80000-000'])
            ->assertRedirect(route('cart.index'));

        $this->get('/cotacao')->assertOk();
        $this->assertNotNull(session('cart.freight'));
    }

    public function test_invalid_cep_is_rejected(): void
    {
        $this->from('/cotacao')
            ->post('/cotacao/frete', ['cep' => 'abc'])
            ->assertSessionHasErrors('cep');
    }
}
