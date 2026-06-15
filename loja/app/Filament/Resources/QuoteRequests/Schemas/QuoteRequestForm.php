<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class QuoteRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pedido')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('WhatsApp')
                            ->disabled(),
                        TextInput::make('city')
                            ->label('Cidade')
                            ->disabled(),
                        TextInput::make('payment_method')
                            ->label('Forma de pagamento')
                            ->formatStateUsing(fn (?string $state) => $state === 'boleto' ? 'Boleto' : 'WhatsApp')
                            ->disabled(),
                        Select::make('status')
                            ->label('Status')
                            ->options(QuoteRequest::STATUSES)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Itens')
                    ->schema([
                        Placeholder::make('items_list')
                            ->label('')
                            ->content(function (?QuoteRequest $record) {
                                if (! $record) {
                                    return '—';
                                }

                                $lines = [];

                                foreach ($record->items ?? [] as $item) {
                                    $product = Product::find($item['product_id'] ?? null);
                                    $variant = ! empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;

                                    $label = $product?->name ?? 'Produto removido';
                                    if ($variant) {
                                        $label .= ' — '.$variant->name;
                                    }

                                    $lines[] = ($item['qty'] ?? 1).'x '.$label;
                                }

                                return new HtmlString(implode('<br>', array_map('e', $lines)));
                            }),
                    ]),

                Section::make('Boleto')
                    ->schema([
                        Select::make('boleto_status')
                            ->label('Status do boleto')
                            ->options(QuoteRequest::BOLETO_STATUSES),
                        Select::make('payment_term_days')
                            ->label('Prazo')
                            ->options([
                                30 => '30 dias',
                                45 => '45 dias',
                                60 => '60 dias',
                            ]),
                        DatePicker::make('due_date')
                            ->label('Vencimento')
                            ->displayFormat('d/m/Y'),
                        Placeholder::make('credit_application_company')
                            ->label('Empresa (ficha cadastral)')
                            ->content(fn (?QuoteRequest $record) => $record?->creditApplication?->company_name ?? '—'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('payment_method') === 'boleto'),
            ])
            ->columns(1);
    }
}
