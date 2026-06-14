<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variações (medidas/capacidades)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da variação')
                    ->helperText('Ex: 90 × 90 × 120 cm — 1.000 kg')
                    ->required()
                    ->columnSpan(2),
                TextInput::make('sku')->label('Código (SKU)'),
                TextInput::make('price')
                    ->label('Preço (R$)')
                    ->helperText('Vazio = usa o preço do produto')
                    ->numeric()
                    ->prefix('R$'),
                TextInput::make('width_cm')->label('Largura (cm)')->numeric(),
                TextInput::make('depth_cm')->label('Comprimento (cm)')->numeric(),
                TextInput::make('height_cm')->label('Altura (cm)')->numeric(),
                TextInput::make('capacity_kg')->label('Capacidade (kg)')->numeric(),
                TextInput::make('weight_kg')->label('Peso (kg)')->numeric(),
                TextInput::make('stock_quantity')->label('Estoque')->numeric(),
                TextInput::make('sort_order')->label('Ordem')->numeric()->default(0),
                Toggle::make('is_active')->label('Ativa')->default(true),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Variação')->searchable(),
                TextColumn::make('capacity_kg')->label('Capacidade (kg)'),
                TextColumn::make('price')->label('Preço')->money('BRL')->placeholder('Preço do produto'),
                TextColumn::make('stock_quantity')->label('Estoque')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Ativa'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nova variação'),
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
