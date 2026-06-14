<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Fotos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory('produtos')
                    ->imageEditor()
                    ->maxSize(8192)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('alt')
                    ->label('Texto alternativo (SEO)')
                    ->helperText('Descreva a foto. Ex: "Big bag novo 90x90x120 com válvula de descarga — Curitiba"'),
                Toggle::make('is_cover')
                    ->label('Foto de capa'),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public')
                    ->imageHeight(60),
                TextColumn::make('alt')->label('Texto alternativo')->limit(60)->placeholder('—'),
                ToggleColumn::make('is_cover')->label('Capa'),
                TextColumn::make('sort_order')->label('Ordem')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Adicionar foto'),
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
