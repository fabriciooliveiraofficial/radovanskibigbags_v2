<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->helperText('Ex: PIX na retirada, 10x sem juros no cartão, Boleto faturado')
                    ->required(),
                TextInput::make('description')
                    ->label('Descrição (opcional)'),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true),
                Toggle::make('show_in_quotes')
                    ->label('Disponível nos orçamentos')
                    ->default(true),
            ])
            ->columns(2);
    }
}
