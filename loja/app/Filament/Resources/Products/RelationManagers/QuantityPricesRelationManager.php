<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuantityPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'quantityPrices';

    protected static ?string $title = 'Preço por quantidade (atacado)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_qty')
                    ->label('A partir de (unidades)')
                    ->numeric()
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
            ->recordTitleAttribute('min_qty')
            ->columns([
                TextColumn::make('min_qty')->label('A partir de (un)')->sortable(),
                TextColumn::make('unit_price')->label('Preço unitário')->money('BRL'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nova faixa de preço'),
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
            ->defaultSort('min_qty');
    }
}
