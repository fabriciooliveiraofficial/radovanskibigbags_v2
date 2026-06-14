<?php

// Cria um orçamento de demonstração e imprime o link público.
// Uso: php artisan tinker scripts/demo-quote.php  (ou via require no tinker)

use App\Models\Customer;
use App\Models\Quote;

$customer = Customer::firstOrCreate(
    ['phone' => '41988887777'],
    ['name' => 'Carlos Mendes', 'company' => 'Recicla Sul Ltda', 'document' => '12.345.678/0001-90', 'city' => 'Curitiba']
);

$quote = Quote::create([
    'customer_id' => $customer->id,
    'status' => 'enviado',
    'valid_until' => now()->addDays(7),
    'shipping_method' => 'retirada',
    'payment_terms' => '10x sem juros no cartão',
    'notes' => "Orçamento válido por 7 dias.\nValores sujeitos a alteração sem aviso prévio.",
]);

$quote->items()->create(['description' => 'Big Bag Lavado 90×90×120 — 1.000 kg', 'qty' => 100, 'unit_price' => 19.90, 'total' => 0, 'sort_order' => 1]);
$quote->items()->create(['description' => 'Big Bag Sujo (sem lavagem) — 1.000 kg', 'qty' => 200, 'unit_price' => 10.90, 'total' => 0, 'sort_order' => 2]);

echo $quote->fresh()->public_token.PHP_EOL;
