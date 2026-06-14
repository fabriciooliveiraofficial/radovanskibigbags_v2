<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da categoria')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->helperText('Ex: big-bags-novos → site.com.br/big-bags-novos-curitiba')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Descrição (aparece na página da categoria — bom para SEO)')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Imagem da categoria')
                    ->image()
                    ->disk('public')
                    ->directory('categorias'),
                TextInput::make('sort_order')
                    ->label('Ordem de exibição')
                    ->numeric()
                    ->default(0),
                TextInput::make('seo_title')
                    ->label('Título SEO')
                    ->maxLength(70),
                Textarea::make('seo_description')
                    ->label('Descrição SEO')
                    ->maxLength(320)
                    ->rows(2),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true),
            ])
            ->columns(2);
    }
}
