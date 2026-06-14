<?php

namespace App\Filament\Resources\UseCases\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UseCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do uso')
                    ->helperText('Ex: Reciclagem, Grãos, Entulho, Areia')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->helperText('Vira a landing page: site.com.br/big-bags-para-{slug}')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Descrição (texto da landing page — bom para SEO)')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('seo_title')
                    ->label('Título SEO')
                    ->maxLength(70),
                Textarea::make('seo_description')
                    ->label('Descrição SEO')
                    ->maxLength(320)
                    ->rows(2),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
            ])
            ->columns(2);
    }
}
