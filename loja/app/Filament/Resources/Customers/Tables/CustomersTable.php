<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Contato')->searchable()->sortable(),
                TextColumn::make('company')->label('Empresa')->searchable()->placeholder('—'),
                TextColumn::make('phone')->label('WhatsApp')->searchable(),
                TextColumn::make('city')->label('Cidade'),
                TextColumn::make('quotes_count')->label('Orçamentos')->counts('quotes'),
                TextColumn::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y')->sortable(),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Customer $record) => 'https://wa.me/'.$record->whatsappNumber(), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
