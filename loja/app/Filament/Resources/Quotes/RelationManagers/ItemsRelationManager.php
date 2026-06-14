<?php

namespace App\Filament\Resources\Quotes\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Itens do orçamento';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                            $set('description', $product->name.($product->dimensionsLabel() ? ' — '.$product->dimensionsLabel() : ''));
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
                            $set('description', $variant->product->name.' — '.$variant->name);
                            if ($variant->effectivePrice() !== null) {
                                $set('unit_price', (string) $variant->effectivePrice());
                            }
                        }
                    })
                    ->visible(fn (Get $get) => filled($get('product_id')))
                    ->columnSpan(2),
                TextInput::make('description')
                    ->label('Descrição do item')
                    ->helperText('Texto que aparece no orçamento. Pode ser um item livre, sem produto do catálogo.')
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
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('description')->label('Item'),
                TextColumn::make('qty')->label('Qtde'),
                TextColumn::make('unit_price')->label('Unitário')->money('BRL'),
                TextColumn::make('total')->label('Total')->money('BRL'),
            ])
            ->headerActions([
                CreateAction::make()->label('Adicionar item'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
