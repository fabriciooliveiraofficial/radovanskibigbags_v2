<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Quote;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            ->label('Cliente (empresa / razão social)')
                            ->relationship('customer', 'company')
                            ->getOptionLabelFromRecordUsing(fn (Customer $r) => ($r->company ?: $r->name).($r->company && $r->name ? ' — '.$r->name : ''))
                            ->searchable(['company', 'name', 'document'])
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('company')->label('Empresa / Razão social')->required(),
                                TextInput::make('name')->label('Nome do contato'),
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

                        Select::make('_ficha_id')
                            ->label('Importar de Ficha Cadastral')
                            ->placeholder('Buscar por empresa, CNPJ ou contato...')
                            ->helperText('Ao selecionar, os dados da ficha são importados automaticamente no campo Cliente acima.')
                            ->options(fn () => CreditApplication::whereIn('status', ['pendente', 'aprovado'])
                                ->orderBy('company_name')
                                ->get()
                                ->mapWithKeys(fn ($f) => [
                                    $f->id => $f->company_name
                                        .($f->trade_name ? ' ('.$f->trade_name.')' : '')
                                        .($f->contact_name ? ' — '.$f->contact_name : '')
                                        .' ['.CreditApplication::STATUSES[$f->status].']',
                                ])
                                ->toArray())
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) =>
                                CreditApplication::whereIn('status', ['pendente', 'aprovado'])
                                    ->where(fn ($q) => $q
                                        ->where('company_name', 'like', "%{$search}%")
                                        ->orWhere('trade_name', 'like', "%{$search}%")
                                        ->orWhere('contact_name', 'like', "%{$search}%")
                                        ->orWhere('document', 'like', "%{$search}%"))
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($f) => [
                                        $f->id => $f->company_name
                                            .($f->contact_name ? ' — '.$f->contact_name : '')
                                            .' ['.CreditApplication::STATUSES[$f->status].']',
                                    ])
                                    ->toArray())
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (! $state) {
                                    return;
                                }

                                $ficha = CreditApplication::find($state);
                                if (! $ficha) {
                                    return;
                                }

                                // Ficha já aprovada tem Customer vinculado — usa direto
                                if ($ficha->customer_id) {
                                    $set('customer_id', (string) $ficha->customer_id);
                                    $set('_ficha_id', null);
                                    return;
                                }

                                // Cria (ou encontra) Customer a partir dos dados da ficha
                                $customer = Customer::firstOrCreate(
                                    ['document' => $ficha->document],
                                    [
                                        'company' => $ficha->company_name,
                                        'name'    => $ficha->contact_name ?? $ficha->company_name,
                                        'phone'   => $ficha->phone ?? '',
                                        'email'   => $ficha->email,
                                        'city'    => $ficha->city,
                                    ]
                                );

                                // Vincula o customer à ficha se ela for aprovada
                                if ($ficha->status === 'aprovado') {
                                    $ficha->forceFill(['customer_id' => $customer->id])->save();
                                }

                                $set('customer_id', (string) $customer->id);
                                $set('_ficha_id', null);
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),
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
