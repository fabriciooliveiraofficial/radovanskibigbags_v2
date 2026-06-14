<?php

namespace App\Services\Shipping;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolve CEP → coordenadas → distância em km, usando apenas serviços gratuitos
 * e com fallback em cada etapa:
 *
 *   Coordenadas: BrasilAPI (v2, traz lat/lng) → ViaCEP + Nominatim
 *   Distância:   OpenRouteService (rota real) → Haversine × 1,3 (fator rodoviário)
 */
class DistanceService
{
    private const ROAD_FACTOR = 1.3;

    /** @return array{lat: float, lng: float, city: string}|null */
    public function coordinatesFromCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        return $this->viaBrasilApi($cep) ?? $this->viaViaCepNominatim($cep);
    }

    /** @return array{lat: float, lng: float, city: string}|null */
    private function viaBrasilApi(string $cep): ?array
    {
        try {
            $response = Http::timeout(6)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            $lat = data_get($data, 'location.coordinates.latitude');
            $lng = data_get($data, 'location.coordinates.longitude');

            if ($lat && $lng) {
                return ['lat' => (float) $lat, 'lng' => (float) $lng, 'city' => (string) data_get($data, 'city', '')];
            }

            // Sem coordenadas: geocodifica cidade+bairro via Nominatim
            return $this->geocode(
                trim(data_get($data, 'street', '').', '.data_get($data, 'neighborhood', '').', '.data_get($data, 'city', '').', '.data_get($data, 'state', ''), ', '),
                (string) data_get($data, 'city', '')
            );
        } catch (\Throwable $e) {
            Log::info('BrasilAPI indisponível para CEP '.$cep.': '.$e->getMessage());

            return null;
        }
    }

    /** @return array{lat: float, lng: float, city: string}|null */
    private function viaViaCepNominatim(string $cep): ?array
    {
        try {
            $response = Http::timeout(6)->get("https://viacep.com.br/ws/{$cep}/json/");

            if (! $response->ok() || $response->json('erro')) {
                return null;
            }

            $data = $response->json();

            return $this->geocode(
                trim(($data['logradouro'] ?? '').', '.($data['bairro'] ?? '').', '.($data['localidade'] ?? '').', '.($data['uf'] ?? ''), ', '),
                (string) ($data['localidade'] ?? '')
            );
        } catch (\Throwable $e) {
            Log::info('ViaCEP indisponível para CEP '.$cep.': '.$e->getMessage());

            return null;
        }
    }

    /** @return array{lat: float, lng: float, city: string}|null */
    private function geocode(string $query, string $city): ?array
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'RadovanskiBigBags/1.0 (loja virtual)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query.', Brasil',
                    'format' => 'json',
                    'limit' => 1,
                ]);

            $hit = $response->ok() ? ($response->json()[0] ?? null) : null;

            if (! $hit) {
                return null;
            }

            return ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon'], 'city' => $city];
        } catch (\Throwable $e) {
            Log::info('Nominatim indisponível: '.$e->getMessage());

            return null;
        }
    }

    /** Distância rodoviária em km entre dois pontos */
    public function roadDistanceKm(array $from, array $to): ?float
    {
        return $this->viaOpenRouteService($from, $to)
            ?? $this->haversineKm($from, $to) * self::ROAD_FACTOR;
    }

    private function viaOpenRouteService(array $from, array $to): ?float
    {
        $apiKey = (string) Setting::get('openroute_api_key', '');

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Authorization' => $apiKey])
                ->get('https://api.openrouteservice.org/v2/directions/driving-car', [
                    'start' => $from['lng'].','.$from['lat'],
                    'end' => $to['lng'].','.$to['lat'],
                ]);

            $meters = $response->ok()
                ? data_get($response->json(), 'features.0.properties.summary.distance')
                : null;

            return $meters !== null ? round($meters / 1000, 1) : null;
        } catch (\Throwable $e) {
            Log::info('OpenRouteService indisponível: '.$e->getMessage());

            return null;
        }
    }

    public function haversineKm(array $from, array $to): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($to['lat'] - $from['lat']);
        $dLng = deg2rad($to['lng'] - $from['lng']);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($from['lat'])) * cos(deg2rad($to['lat'])) * sin($dLng / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
