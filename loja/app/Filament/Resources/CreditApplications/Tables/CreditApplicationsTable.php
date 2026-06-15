<?php

namespace App\Filament\Resources\CreditApplications\Tables;

use App\Models\CreditApplication;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CreditApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Razão social')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document')
                    ->label('CNPJ')
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label('Contato')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('WhatsApp'),
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
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CreditApplication::STATUSES),
            ])
            ->recordActions([
                Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CreditApplication $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->modalDescription('A empresa poderá usar "Pagar com boleto" ao finalizar pedidos no site.')
                    ->action(function (CreditApplication $record) {
                        $customer = Customer::firstOrCreate(
                            ['document' => $record->document],
                            [
                                'name' => $record->contact_name,
                                'company' => $record->company_name,
                                'phone' => $record->phone,
                                'email' => $record->email,
                                'cep' => $record->cep,
                                'address' => $record->address,
                                'city' => $record->city ?: 'Curitiba',
                                'state' => $record->state ?: 'PR',
                                'notes' => $record->notes,
                            ]
                        );

                        $record->forceFill([
                            'status' => 'aprovado',
                            'reviewed_at' => now(),
                            'customer_id' => $customer->id,
                        ])->save();
                    }),
                Action::make('reprovar')
                    ->label('Reprovar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CreditApplication $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('review_notes')
                            ->label('Motivo (opcional)')
                            ->rows(3),
                    ])
                    ->action(function (CreditApplication $record, array $data) {
                        $record->forceFill([
                            'status' => 'reprovado',
                            'reviewed_at' => now(),
                            'review_notes' => $data['review_notes'] ?? $record->review_notes,
                        ])->save();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
