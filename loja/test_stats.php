<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$monthStart = now()->startOfMonth();
echo json_encode([
    'quotesThisMonth' => App\Models\Quote::where('created_at', '>=', $monthStart)->count(),
    'aguardando' => App\Models\Quote::whereIn('status', ['enviado', 'visualizado'])->count(),
    'produtosAtivos' => App\Models\Product::where('is_active', true)->count(),
    'clientes' => App\Models\Customer::count()
]);
