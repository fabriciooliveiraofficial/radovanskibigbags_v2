<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do atributo')
                    ->helperText('Ex: Cor, Laminado, Gramatura')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'select' => 'Lista de opções',
                        'boolean' => 'Sim/Não',
                        'number' => 'Número',
                    ])
                    ->default('select')
                    ->required(),
                TextInput::make('unit')
                    ->label('Unidade (opcional)')
                    ->helperText('Ex: g/m², cm'),
                Toggle::make('is_filterable')
                    ->label('Aparece como filtro no site')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
            ])
            ->columns(2);
    }
}
