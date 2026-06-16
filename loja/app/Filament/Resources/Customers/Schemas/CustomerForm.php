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
                    ->label('CNPJ / CPF')
                    ->extraInputAttributes([
                        'x-mask:dynamic' => '$input.length > 14 ? \'99.999.999/9999-99\' : \'999.999.999-99\'',
                    ]),
                TextInput::make('phone')
                    ->label('WhatsApp')
                    ->tel()
                    ->required()
                    ->helperText('Com DDD. Ex: (41) 99999-9999')
                    ->extraInputAttributes([
                        'x-mask:dynamic' => '$input.replace(/\D/g, \'\').length > 10 ? \'(99) 99999-9999\' : \'(99) 9999-9999\'',
                    ]),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email(),
                TextInput::make('cep')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }
                        $cleanCep = preg_replace('/\D/', '', $state);
                        if (strlen($cleanCep) !== 8) {
                            return;
                        }
                        try {
                            $response = app(\App\Http\Controllers\CepController::class)->lookup($cleanCep);
                            if ($response->isSuccessful()) {
                                $data = json_decode($response->getContent(), true);
                                if ($data && !isset($data['error'])) {
                                    $set('address', trim(($data['street'] ?? '') . ', ' . ($data['neighborhood'] ?? ''), ', '));
                                    $set('city', $data['city'] ?? '');
                                    $set('state', $data['state'] ?? '');
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Erro ao buscar CEP no form de cliente: " . $e->getMessage());
                        }
                    }),
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
