<?php

namespace App\Filament\Resources\CreditApplications\Schemas;

use App\Models\CreditApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreditApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Vincular cliente existente (opcional)')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }
                        $customer = \App\Models\Customer::find($state);
                        if ($customer) {
                            $set('company_name', $customer->company ?: $customer->name);
                            $set('trade_name', $customer->company ?: $customer->name);
                            $set('document', $customer->document);
                            $set('contact_name', $customer->name);
                            $set('phone', $customer->phone);
                            $set('email', $customer->email);
                            $set('cep', $customer->cep);
                            $set('address', $customer->address);
                            $set('city', $customer->city);
                            $set('state', $customer->state);
                        }
                    })
                    ->columnSpanFull(),
                TextInput::make('company_name')
                    ->label('Razão social')
                    ->required(),
                TextInput::make('trade_name')
                    ->label('Nome fantasia'),
                TextInput::make('document')
                    ->label('CNPJ')
                    ->mask('99.999.999/9999-99')
                    ->required(),
                TextInput::make('state_registration')
                    ->label('Inscrição estadual'),
                TextInput::make('contact_name')
                    ->label('Nome do responsável')
                    ->required(),
                TextInput::make('phone')
                    ->label('WhatsApp')
                    ->tel()
                    ->required()
                    ->extraInputAttributes([
                        'x-mask:dynamic' => '$input.replace(/\D/g, "").length > 10 ? "(99) 99999-9999" : "(99) 9999-9999"',
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
                            \Illuminate\Support\Facades\Log::error("Erro ao buscar CEP no form: " . $e->getMessage());
                        }
                    }),
                TextInput::make('address')
                    ->label('Endereço'),
                TextInput::make('city')
                    ->label('Cidade'),
                TextInput::make('state')
                    ->label('UF')
                    ->maxLength(2),
                Select::make('status')
                    ->label('Status')
                    ->options(CreditApplication::STATUSES)
                    ->default('pendente')
                    ->required(),
                Textarea::make('notes')
                    ->label('Observações da empresa')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('review_notes')
                    ->label('Notas da análise (internas)')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }
}
