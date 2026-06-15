<?php

namespace App\Filament\Resources\CreditApplications\Schemas;

use App\Models\CreditApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreditApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_name')
                    ->label('Razão social')
                    ->required(),
                TextInput::make('trade_name')
                    ->label('Nome fantasia'),
                TextInput::make('document')
                    ->label('CNPJ')
                    ->required(),
                TextInput::make('state_registration')
                    ->label('Inscrição estadual'),
                TextInput::make('contact_name')
                    ->label('Nome do responsável')
                    ->required(),
                TextInput::make('phone')
                    ->label('WhatsApp')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email(),
                TextInput::make('cep')
                    ->label('CEP'),
                TextInput::make('address')
                    ->label('Endereço'),
                TextInput::make('city')
                    ->label('Cidade'),
                TextInput::make('state')
                    ->label('UF')
                    ->maxLength(2),
                Select::make('status')
                    ->label('Status')
                    ->options(CreditApplication::STATUSES)
                    ->default('pendente')
                    ->required(),
                Textarea::make('notes')
                    ->label('Observações da empresa')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('review_notes')
                    ->label('Notas da análise (internas)')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }
}
