<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CreditApplication;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'creditApplications';

    protected static ?string $title = 'Fichas Cadastrais';

    public function form(Schema $schema): Schema
    {
        // We don't necessarily need inline creation/edit here, since they are handled by the main resource.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->emptyStateHeading('Nenhuma ficha cadastral')
            ->emptyStateDescription('Nenhuma ficha cadastral está vinculada a este cliente.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Razão social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document')
                    ->label('CNPJ')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CreditApplication::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pendente' => 'warning',
                        'aprovado' => 'success',
                        'reprovado' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Recebida em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Editar ficha')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CreditApplication $record) => \App\Filament\Resources\CreditApplications\CreditApplicationResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
