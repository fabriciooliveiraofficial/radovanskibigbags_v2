<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Pedidos\PedidosResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePedido extends CreateRecord
{
    protected static string $resource = PedidosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'pedido';
        return $data;
    }
}
