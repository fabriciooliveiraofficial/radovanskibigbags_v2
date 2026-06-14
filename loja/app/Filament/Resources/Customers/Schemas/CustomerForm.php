<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do contato')
                    ->required(),
                TextInput::make('company')
                    ->label('Empresa'),
                TextInput::make('document')
                    ->label('CNPJ / CPF'),
                TextInput::make('phone')
                    ->label('WhatsApp')
                    ->tel()
                    ->required()
                    ->helperText('Com DDD. Ex: (41) 99999-9999'),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email(),
                TextInput::make('cep')
                    ->label('CEP'),
                TextInput::make('address')
                    ->label('Endereço'),
                TextInput::make('city')
                    ->label('Cidade')
                    ->default('Curitiba'),
                TextInput::make('state')
                    ->label('UF')
                    ->default('PR')
                    ->maxLength(2),
                Textarea::make('notes')
                    ->label('Anotações internas')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }
}
