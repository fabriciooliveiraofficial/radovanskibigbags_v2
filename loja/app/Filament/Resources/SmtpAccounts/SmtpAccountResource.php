<?php

namespace App\Filament\Resources\SmtpAccounts;

use App\Filament\Resources\SmtpAccounts\Pages\CreateSmtpAccount;
use App\Filament\Resources\SmtpAccounts\Pages\EditSmtpAccount;
use App\Filament\Resources\SmtpAccounts\Pages\ListSmtpAccounts;
use App\Filament\Resources\SmtpAccounts\Schemas\SmtpAccountForm;
use App\Filament\Resources\SmtpAccounts\Tables\SmtpAccountsTable;
use App\Models\SmtpAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SmtpAccountResource extends Resource
{
    protected static ?string $model = SmtpAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'servidor de e-mail';

    protected static ?string $pluralModelLabel = 'servidores de e-mail (SMTP)';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SmtpAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmtpAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSmtpAccounts::route('/'),
            'create' => CreateSmtpAccount::route('/create'),
            'edit'   => EditSmtpAccount::route('/{record}/edit'),
        ];
    }
}
