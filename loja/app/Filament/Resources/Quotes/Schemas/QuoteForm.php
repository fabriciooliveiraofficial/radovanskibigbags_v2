<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Services\Shipping\FreightCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        $updateFreight = function (Set $set, Get $get, ?Model $record = null) {
            $method = $get('shipping_method');
            if (!$method || $method === 'retirada') {
                $set('shipping_cost', 0);
                $set('shipping_deadline', null);
                $set('shipping_carrier', null);
                return;
            }

            $cep = null;
            if ($get('has_different_delivery_address')) {
                $cep = $get('shipping_cep');
            } else {
                $customerId = $get('customer_id');
                if ($customerId) {
                    $customer = Customer::find($customerId);
                    if ($customer && $customer->cep) {
                        $cep = $customer->cep;
                    }
                }
            }

            if (!$cep) {
                return;
            }

            $items = [];
            $formItems = $get('items') ?? [];
            if (!empty($formItems)) {
                foreach ($formItems as $itemData) {
                    $weight = 0.5;
                    if (!empty($itemData['product_id'])) {
                        $product = \App\Models\Product::find($itemData['product_id']);
                        if ($product) {
                            $weight = $product->weight_kg ?? 0.5;
                        }
                    }
                    $items[] = [
                        'weight_kg' => (float) $weight,
                        'qty' => (int) ($itemData['qty'] ?? 1),
                    ];
                }
            } elseif ($record && $record->exists) {
                foreach ($record->items as $item) {
                    $items[] = [
                        'weight_kg' => (float) ($item->weight_kg ?? ($item->product->weight_kg ?? 0.5)),
                        'qty' => (int) $item->qty,
                    ];
                }
            }

            try {
                $calculator = app(FreightCalculator::class);
                $result = $calculator->quote($cep, $items);

                if (!empty($result['options'])) {
                    foreach ($result['options'] as $option) {
                        if ($option['method'] === $method) {
                            $set('shipping_cost', $option['cost'] ?? 0);
                            $set('shipping_deadline', $option['deadline']);
                            $set('shipping_carrier', $option['carrier']);
                            return;
                        }
                    }
                }

                $set('shipping_cost', null);
                $set('shipping_deadline', null);
                $set('shipping_carrier', null);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erro ao calcular frete no admin: " . $e->getMessage());
            }
        };

        return $schema
            ->components([
                Section::make('Cliente e validade')
                    ->schema([
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
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record, ?string $state) use ($updateFreight) {
                                if (! $state) {
                                    return;
                                }

                                $ficha = CreditApplication::find($state);
                                if (! $ficha) {
                                    return;
                                }

                                // Ficha já aprovada tem Customer vinculado — usa direto
                                if ($ficha->customer_id) {
                                    $set('customer_id', $ficha->customer_id);
                                    $set('_ficha_id', null);
                                    $updateFreight($set, $get, $record);
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
                                        'address' => $ficha->address,
                                        'cep'     => $ficha->cep,
                                        'state'   => $ficha->state,
                                    ]
                                );

                                // Vincula o customer à ficha se ela for aprovada
                                if ($ficha->status === 'aprovado') {
                                    $ficha->forceFill(['customer_id' => $customer->id])->save();
                                }

                                $set('customer_id', $customer->id);
                                $set('_ficha_id', null);
                                $updateFreight($set, $get, $record);
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('customer_id')
                            ->label('Cliente (empresa / razão social)')
                            ->options(fn () => Customer::orderBy('company')->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => ($c->company ?: $c->name)
                                        .($c->company && $c->name ? ' — '.$c->name : ''),
                                ]))
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('company')->label('Empresa / Razão social')->required(),
                                TextInput::make('name')->label('Nome do contato'),
                                TextInput::make('phone')->label('WhatsApp')->tel()->required(),
                                TextInput::make('email')->label('E-mail')->email(),
                                TextInput::make('address')->label('Endereço'),
                                TextInput::make('city')->label('Cidade')->default('Curitiba'),
                                TextInput::make('state')->label('Estado')->default('PR'),
                                TextInput::make('cep')->label('CEP'),
                            ])
                            ->createOptionUsing(fn (array $data) => Customer::create($data)->id)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record) use ($updateFreight) {
                                $updateFreight($set, $get, $record);
                            }),

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
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record) use ($updateFreight) {
                                $updateFreight($set, $get, $record);
                            }),
                        TextInput::make('shipping_cost')
                            ->label('Valor do frete (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->disabled(fn (Get $get) => $get('shipping_method') === 'retirada')
                            ->dehydrated()
                            ->hintAction(
                                Action::make('recalculateShipping')
                                    ->label('Calcular')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Set $set, Get $get, ?Model $record) use ($updateFreight) {
                                        $updateFreight($set, $get, $record);
                                    })
                            ),
                        TextInput::make('shipping_deadline')
                            ->label('Prazo')
                            ->placeholder('Ex: 1 a 2 dias úteis'),
                        TextInput::make('shipping_carrier')
                            ->label('Transportadora')
                            ->visible(fn (Get $get) => $get('shipping_method') === 'transportadora'),

                        Toggle::make('has_different_delivery_address')
                            ->label('Endereço de entrega diferente')
                            ->default(false)
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record, $state) use ($updateFreight) {
                                if (!$state) {
                                    $set('shipping_cep', null);
                                    $set('delivery_address', null);
                                } else {
                                    if ($get('shipping_method') === 'retirada') {
                                        $set('shipping_method', 'transportadora');
                                    }
                                }
                                $updateFreight($set, $get, $record);
                            })
                            ->columnSpanFull(),

                        TextInput::make('shipping_cep')
                            ->label('CEP de entrega')
                            ->placeholder('00000-000')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => $get('has_different_delivery_address'))
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record, $state) use ($updateFreight) {
                                if (!$state) return;
                                $cleanCep = preg_replace('/\D/', '', $state);
                                if (strlen($cleanCep) === 8) {
                                    try {
                                        $response = \Illuminate\Support\Facades\Http::timeout(3)
                                            ->withoutVerifying()
                                            ->get("https://viacep.com.br/ws/{$cleanCep}/json/");
                                        if ($response->successful()) {
                                            $data = $response->json();
                                            if (!isset($data['erro']) || !$data['erro']) {
                                                $street = $data['logradouro'] ?? '';
                                                $neighborhood = $data['bairro'] ?? '';
                                                $city = $data['localidade'] ?? '';
                                                $stateCode = $data['uf'] ?? '';
                                                
                                                $formatted = array_filter([$street, $neighborhood, $city, $stateCode]);
                                                $set('delivery_address', implode(', ', $formatted) . ', Nº ');
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::warning("ViaCEP lookup failed in form: " . $e->getMessage());
                                    }
                                }
                                $updateFreight($set, $get, $record);
                            }),

                        Textarea::make('delivery_address')
                            ->label('Endereço de entrega')
                            ->placeholder('Rua..., Número..., Bairro..., Cidade..., Estado..., CEP...')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('has_different_delivery_address'))
                            ->columnSpanFull(),
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

                Section::make('Itens do orçamento')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->addActionLabel('Adicionar item')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produto do catálogo (opcional)')
                                    ->options(fn () => Product::orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if (! $state) {
                                            return;
                                        }
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->name.($product->dimensionsLabel() ? "\n".$product->dimensionsLabel() : ''));
                                            if ($product->price !== null) {
                                                $set('unit_price', (string) $product->price);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                Select::make('product_variant_id')
                                    ->label('Variação')
                                    ->options(fn (Get $get) => $get('product_id')
                                        ? ProductVariant::where('product_id', $get('product_id'))->pluck('name', 'id')->all()
                                        : [])
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if (! $state) {
                                            return;
                                        }
                                        $variant = ProductVariant::find($state);
                                        if ($variant) {
                                            $set('description', $variant->product->name."\n".$variant->name);
                                            if ($variant->effectivePrice() !== null) {
                                                $set('unit_price', (string) $variant->effectivePrice());
                                            }
                                        }
                                    })
                                    ->visible(fn (Get $get) => filled($get('product_id')))
                                    ->columnSpan(2),
                                Textarea::make('description')
                                    ->label('Descrição do item')
                                    ->helperText('Texto que aparece no orçamento. Use quebras de linha para separar o produto dos seus atributos.')
                                    ->rows(2)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('qty')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                TextInput::make('unit_price')
                                    ->label('Preço unitário (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required(),
                                Select::make('discount_type')
                                    ->label('Tipo de desconto')
                                    ->options([
                                        'percent' => 'Percentual (%)',
                                        'fixed' => 'Valor fixo (R$)',
                                    ])
                                    ->placeholder('Sem desconto')
                                    ->live(),
                                TextInput::make('discount_value')
                                    ->label('Desconto do item')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn (Get $get) => filled($get('discount_type'))),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible(),

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
