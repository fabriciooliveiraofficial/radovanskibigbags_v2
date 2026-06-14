<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->dimensionsLabel()),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable(),
                TextColumn::make('condition')
                    ->label('Condição')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Product::CONDITIONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'novo' => 'success',
                        'lavado' => 'info',
                        'sujo' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable()
                    ->placeholder('Sob consulta'),
                IconColumn::make('price_visible')
                    ->label('Preço visível')
                    ->boolean(),
                TextColumn::make('stock_quantity')
                    ->label('Estoque')
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
                SelectFilter::make('condition')
                    ->label('Condição')
                    ->options(Product::CONDITIONS),
                TernaryFilter::make('is_active')
                    ->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->excludeAttributes(['slug'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['name'] = ($data['name'] ?? '').' (cópia)';
                        $data['slug'] = Str::slug(($data['name'] ?? 'produto')).'-'.uniqid();
                        $data['is_active'] = false;

                        return $data;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
