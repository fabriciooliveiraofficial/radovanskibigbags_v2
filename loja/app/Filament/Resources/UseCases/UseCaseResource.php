<?php

namespace App\Filament\Resources\UseCases;

use App\Filament\Resources\UseCases\Pages\CreateUseCase;
use App\Filament\Resources\UseCases\Pages\EditUseCase;
use App\Filament\Resources\UseCases\Pages\ListUseCases;
use App\Filament\Resources\UseCases\Schemas\UseCaseForm;
use App\Filament\Resources\UseCases\Tables\UseCasesTable;
use App\Models\UseCase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UseCaseResource extends Resource
{
    protected static ?string $model = UseCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?string $modelLabel = 'uso recomendado';

    protected static ?string $pluralModelLabel = 'usos recomendados';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return UseCaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UseCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUseCases::route('/'),
            'create' => CreateUseCase::route('/create'),
            'edit' => EditUseCase::route('/{record}/edit'),
        ];
    }
}
