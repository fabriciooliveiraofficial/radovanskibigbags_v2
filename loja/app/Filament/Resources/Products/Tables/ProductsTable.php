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
                    ->excludeAttributes(['id', 'created_at', 'updated_at', 'slug'])
                    ->mutateRecordDataUsing(function (array $data, Product $record): array {
                        $data['name'] = ($data['name'] ?? '').' (cópia)';
                        $data['slug'] = \App\Filament\Resources\Products\Schemas\ProductForm::generateUniqueSlug($data['name']);
                        $data['is_active'] = (bool) ($record->is_active ?? true);

                        return $data;
                    })
                    ->after(function (Product $record, Product $replica): void {
                        foreach ($record->useCases as $useCase) {
                            $replica->useCases()->attach($useCase->id);
                        }
                        foreach ($record->images as $image) {
                            $newImage = $image->replicate(['id', 'product_id', 'created_at', 'updated_at']);
                            $newImage->product_id = $replica->id;
                            $newImage->save();
                        }
                        foreach ($record->variants as $variant) {
                            $newVariant = $variant->replicate(['id', 'product_id', 'created_at', 'updated_at']);
                            $newVariant->product_id = $replica->id;
                            $newVariant->save();
                        }
                        foreach ($record->quantityPrices as $qp) {
                            $newQp = $qp->replicate(['id', 'product_id', 'created_at', 'updated_at']);
                            $newQp->product_id = $replica->id;
                            $newQp->save();
                        }
                        foreach ($record->attributeValues as $attr) {
                            $newAttr = $attr->replicate(['id', 'product_id', 'created_at', 'updated_at']);
                            $newAttr->product_id = $replica->id;
                            $newAttr->save();
                        }
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
