<?php

namespace App\Services\Shipping;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calcula as opções de frete para um CEP de destino.
 *
 * Modalidades:
 *  1. Retirada (sempre disponível, grátis)
 *  2. Entrega própria por km (dentro do raio configurado)
 *  3. Transportadora com fallback: Melhor Envio → SuperFrete → Frenet
 *
 * Se tudo falhar, retorna apenas retirada + "frete sob consulta" — o cálculo
 * nunca trava a cotação do cliente.
 */
class FreightCalculator
{
    public function __construct(private readonly DistanceService $distance)
    {
    }

    /**
     * @param  array{weight_kg: float, qty: int}  ...$items peso unitário e quantidade
     * @return array{options: list<array{method: string, label: string, cost: ?float, deadline: ?string, carrier: ?string}>, fallback: bool}
     */
    public function quote(string $destinationCep, array $items): array
    {
        $options = [[
            'method' => 'retirada',
            'label' => 'Retirada no depósito (Curitiba)',
            'cost' => 0.0,
            'deadline' => 'Imediata, com agendamento',
            'carrier' => null,
        ]];

        $totalWeight = max(0.1, array_sum(array_map(
            fn (array $item) => (float) ($item['weight_kg'] ?? 0.5) * (int) ($item['qty'] ?? 1),
            $items
        )));

        $fallback = false;

        if ($delivery = $this->ownDelivery($destinationCep)) {
            $options[] = $delivery;
        }

        if ($carrier = $this->carrierQuote($destinationCep, $totalWeight)) {
            $options[] = $carrier;
        } elseif (count($options) === 1) {
            // Nenhuma API respondeu: orienta a consulta manual
            $fallback = true;
            $options[] = [
                'method' => 'sob_consulta',
                'label' => 'Entrega — valor sob consulta no WhatsApp',
                'cost' => null,
                'deadline' => null,
                'carrier' => null,
            ];
        }

        return ['options' => $options, 'fallback' => $fallback];
    }

    /** Entrega própria por km dentro do raio configurado */
    private function ownDelivery(string $destinationCep): ?array
    {
        $originCep = (string) Setting::get('shipping_origin_cep', '');
        $pricePerKm = (float) Setting::get('shipping_price_per_km', 0);

        if ($originCep === '' || $pricePerKm <= 0) {
            return null;
        }

        $origin = $this->distance->coordinatesFromCep($originCep);
        $destination = $this->distance->coordinatesFromCep($destinationCep);

        if (! $origin || ! $destination) {
            return null;
        }

        $km = $this->distance->roadDistanceKm($origin, $destination);

        if ($km === null) {
            return null;
        }

        $maxRadius = (float) Setting::get('shipping_max_radius_km', 0);

        if ($maxRadius > 0 && $km > $maxRadius) {
            return null; // fora do raio: transportadora assume
        }

        $minFee = (float) Setting::get('shipping_min_fee', 0);
        $cost = max($minFee, round($km * $pricePerKm, 2));

        return [
            'method' => 'entrega_propria',
            'label' => sprintf('Entrega própria (%s km)', number_format($km, 1, ',', '.')),
            'cost' => $cost,
            'deadline' => (string) Setting::get('shipping_delivery_days', '1 a 2 dias úteis'),
            'carrier' => null,
        ];
    }

    /** Cotação por transportadora com cadeia de fallback */
    private function carrierQuote(string $destinationCep, float $totalWeightKg): ?array
    {
        $originCep = preg_replace('/\D/', '', (string) Setting::get('shipping_origin_cep', ''));
        $destinationCep = preg_replace('/\D/', '', $destinationCep);

        if ($originCep === '' || strlen($destinationCep) !== 8) {
            return null;
        }

        // Pacote de cotação: big bags dobrados são volumosos mas leves
        $package = [
            'weight' => $totalWeightKg,
            'width' => 40,
            'height' => 30,
            'length' => 60,
        ];

        return $this->viaMelhorEnvio($originCep, $destinationCep, $package)
            ?? $this->viaSuperFrete($originCep, $destinationCep, $package)
            ?? $this->viaFrenet($originCep, $destinationCep, $package);
    }

    private function viaMelhorEnvio(string $origin, string $destination, array $package): ?array
    {
        $token = (string) Setting::get('melhorenvio_token', '');

        if ($token === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withToken($token)
                ->withHeaders(['User-Agent' => 'RadovanskiBigBags (contato via site)'])
                ->post('https://melhorenvio.com.br/api/v2/me/shipment/calculate', [
                    'from' => ['postal_code' => $origin],
                    'to' => ['postal_code' => $destination],
                    'package' => $package,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $best = collect($response->json())
                ->filter(fn ($option) => empty($option['error']) && ! empty($option['price']))
                ->sortBy(fn ($option) => (float) $option['price'])
                ->first();

            if (! $best) {
                return null;
            }

            return [
                'method' => 'transportadora',
                'label' => 'Transportadora ('.data_get($best, 'company.name', 'parceira').')',
                'cost' => (float) $best['price'],
                'deadline' => ($best['delivery_time'] ?? '?').' dias úteis',
                'carrier' => data_get($best, 'company.name'),
            ];
        } catch (\Throwable $e) {
            Log::info('Melhor Envio indisponível: '.$e->getMessage());

            return null;
        }
    }

    private function viaSuperFrete(string $origin, string $destination, array $package): ?array
    {
        $token = (string) Setting::get('superfrete_token', '');

        if ($token === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withToken($token)
                ->withHeaders(['User-Agent' => 'RadovanskiBigBags (contato via site)'])
                ->post('https://api.superfrete.com/api/v0/calculator', [
                    'from' => ['postal_code' => $origin],
                    'to' => ['postal_code' => $destination],
                    'services' => '1,2,17',
                    'package' => $package,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $best = collect($response->json())
                ->filter(fn ($option) => empty($option['has_error']) && ! empty($option['price']))
                ->sortBy(fn ($option) => (float) $option['price'])
                ->first();

            if (! $best) {
                return null;
            }

            return [
                'method' => 'transportadora',
                'label' => 'Transportadora ('.($best['name'] ?? 'parceira').')',
                'cost' => (float) $best['price'],
                'deadline' => data_get($best, 'delivery_time', '?').' dias úteis',
                'carrier' => $best['name'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::info('SuperFrete indisponível: '.$e->getMessage());

            return null;
        }
    }

    private function viaFrenet(string $origin, string $destination, array $package): ?array
    {
        $token = (string) Setting::get('frenet_token', '');

        if ($token === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['token' => $token])
                ->post('https://api.frenet.com.br/shipping/quote', [
                    'SellerCEP' => $origin,
                    'RecipientCEP' => $destination,
                    'ShipmentInvoiceValue' => 100,
                    'ShippingItemArray' => [[
                        'Weight' => $package['weight'],
                        'Width' => $package['width'],
                        'Height' => $package['height'],
                        'Length' => $package['length'],
                        'Quantity' => 1,
                    ]],
                ]);

            if (! $response->ok()) {
                return null;
            }

            $best = collect(data_get($response->json(), 'ShippingSevicesArray', []))
                ->filter(fn ($option) => empty($option['Error']) && ! empty($option['ShippingPrice']))
                ->sortBy(fn ($option) => (float) $option['ShippingPrice'])
                ->first();

            if (! $best) {
                return null;
            }

            return [
                'method' => 'transportadora',
                'label' => 'Transportadora ('.($best['Carrier'] ?? 'parceira').')',
                'cost' => (float) $best['ShippingPrice'],
                'deadline' => ($best['DeliveryTime'] ?? '?').' dias úteis',
                'carrier' => $best['Carrier'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::info('Frenet indisponível: '.$e->getMessage());

            return null;
        }
    }
}
