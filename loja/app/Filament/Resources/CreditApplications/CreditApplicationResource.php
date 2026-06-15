<?php

namespace App\Filament\Resources\CreditApplications;

use App\Filament\Resources\CreditApplications\Pages\CreateCreditApplication;
use App\Filament\Resources\CreditApplications\Pages\EditCreditApplication;
use App\Filament\Resources\CreditApplications\Pages\ListCreditApplications;
use App\Filament\Resources\CreditApplications\Schemas\CreditApplicationForm;
use App\Filament\Resources\CreditApplications\Tables\CreditApplicationsTable;
use App\Models\CreditApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CreditApplicationResource extends Resource
{
    protected static ?string $model = CreditApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Vendas';

    protected static ?string $modelLabel = 'ficha cadastral';

    protected static ?string $pluralModelLabel = 'fichas cadastrais';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CreditApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditApplicationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditApplications::route('/'),
            'create' => CreateCreditApplication::route('/create'),
            'edit' => EditCreditApplication::route('/{record}/edit'),
        ];
    }
}
