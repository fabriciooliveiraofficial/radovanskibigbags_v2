<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Models\PaymentMethod;
use App\Models\Quote;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cliente e validade')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nome do contato')->required(),
                                TextInput::make('company')->label('Empresa'),
                                TextInput::make('phone')->label('WhatsApp')->tel()->required(),
                                TextInput::make('email')->label('E-mail')->email(),
                                TextInput::make('city')->label('Cidade')->default('Curitiba'),
                            ])
                            ->required(),
                        DatePicker::make('valid_until')
                            ->label('Válido até')
                            ->default(now()->addDays(7))
                            ->displayFormat('d/m/Y'),
                        Select::make('status')
                            ->label('Status')
                            ->options(Quote::STATUSES)
                            ->default('rascunho')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Frete / Retirada')
                    ->schema([
                        Select::make('shipping_method')
                            ->label('Modalidade')
                            ->options(Quote::SHIPPING_METHODS)
                            ->default('retirada')
                            ->live(),
                        TextInput::make('shipping_cost')
                            ->label('Valor do frete (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->disabled(fn (Get $get) => $get('shipping_method') === 'retirada')
                            ->dehydrated(),
                        TextInput::make('shipping_deadline')
                            ->label('Prazo')
                            ->placeholder('Ex: 1 a 2 dias úteis'),
                        TextInput::make('shipping_carrier')
                            ->label('Transportadora')
                            ->visible(fn (Get $get) => $get('shipping_method') === 'transportadora'),
                    ])
                    ->columns(4),

                Section::make('Desconto e condições')
                    ->schema([
                        Select::make('discount_type')
                            ->label('Tipo de desconto')
                            ->options([
                                'percent' => 'Percentual (%)',
                                'fixed' => 'Valor fixo (R$)',
                            ])
                            ->placeholder('Sem desconto')
                            ->live(),
                        TextInput::make('discount_value')
                            ->label('Desconto')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Get $get) => filled($get('discount_type'))),
                        Select::make('payment_terms')
                            ->label('Condição de pagamento')
                            ->options(fn () => PaymentMethod::where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'name')
                                ->all())
                            ->searchable()
                            ->helperText('Cadastre opções no menu "Formas de pagamento"'),
                    ])
                    ->columns(3),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observações (visíveis ao cliente no orçamento)')
                            ->rows(3)
                            ->default("Orçamento válido por 7 dias.\nValores sujeitos a alteração sem aviso prévio."),
                        Textarea::make('internal_notes')
                            ->label('Anotações internas (não aparecem para o cliente)')
                            ->rows(2),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->columns(1);
    }
}
