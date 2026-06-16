<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CepController extends Controller
{
    public function lookup(string $cep): JsonResponse
    {
        $cleanCep = preg_replace('/\D/', '', $cep);

        if (strlen($cleanCep) !== 8) {
            return response()->json(['error' => 'CEP inválido.'], 400);
        }

        // 1. BrasilAPI v2
        try {
            $response = Http::timeout(3)->withoutVerifying()->get("https://brasilapi.com.br/api/cep/v2/{$cleanCep}");
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'cep' => $this->formatCep($cleanCep),
                    'street' => $data['street'] ?? '',
                    'neighborhood' => $data['neighborhood'] ?? '',
                    'city' => $data['city'] ?? '',
                    'state' => $data['state'] ?? '',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("BrasilAPI failed for CEP {$cleanCep}: " . $e->getMessage());
        }

        // 2. ViaCEP
        try {
            $response = Http::timeout(3)->withoutVerifying()->get("https://viacep.com.br/ws/{$cleanCep}/json/");
            if ($response->successful()) {
                $data = $response->json();
                if (!isset($data['erro']) || !$data['erro']) {
                    return response()->json([
                        'cep' => $this->formatCep($cleanCep),
                        'street' => $data['logradouro'] ?? '',
                        'neighborhood' => $data['bairro'] ?? '',
                        'city' => $data['localidade'] ?? '',
                        'state' => $data['uf'] ?? '',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("ViaCEP failed for CEP {$cleanCep}: " . $e->getMessage());
        }

        // 3. OpenCEP
        try {
            $response = Http::timeout(3)->withoutVerifying()->get("https://opencep.com/v1/{$cleanCep}");
            if ($response->successful()) {
                $data = $response->json();
                if (!isset($data['erro']) || !$data['erro']) {
                    return response()->json([
                        'cep' => $this->formatCep($cleanCep),
                        'street' => $data['logradouro'] ?? '',
                        'neighborhood' => $data['bairro'] ?? '',
                        'city' => $data['localidade'] ?? '',
                        'state' => $data['uf'] ?? '',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("OpenCEP failed for CEP {$cleanCep}: " . $e->getMessage());
        }

        return response()->json(['error' => 'CEP não encontrado nas bases de dados.'], 404);
    }

    private function formatCep(string $cep): string
    {
        return substr($cep, 0, 5) . '-' . substr($cep, 5);
    }
}
