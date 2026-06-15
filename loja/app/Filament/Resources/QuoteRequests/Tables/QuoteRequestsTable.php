<?php

namespace App\Filament\Resources\QuoteRequests\Tables;

use App\Models\QuoteRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuoteRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Cidade')
                    ->placeholder('—'),
                TextColumn::make('items')
                    ->label('Itens')
                    ->formatStateUsing(fn (?array $state) => count($state ?? [])),
                TextColumn::make('payment_method')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'boleto' ? 'Boleto' : 'WhatsApp')
                    ->color(fn (string $state) => $state === 'boleto' ? 'info' : 'gray'),
                TextColumn::make('boleto_status')
                    ->label('Status do boleto')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? (QuoteRequest::BOLETO_STATUSES[$state] ?? $state) : null)
                    ->color(fn (?string $state) => match ($state) {
                        'aguardando_aprovacao' => 'warning',
                        'aprovado' => 'success',
                        'rejeitado' => 'danger',
                        'emitido' => 'info',
                        'pago' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => QuoteRequest::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'novo' => 'warning',
                        'atendido' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(QuoteRequest::STATUSES),
                SelectFilter::make('payment_method')
                    ->label('Pagamento')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'boleto' => 'Boleto',
                    ]),
            ])
            ->recordActions([
                Action::make('aprovarBoleto')
                    ->label('Aprovar boleto')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (QuoteRequest $record) => $record->payment_method === 'boleto' && $record->boleto_status === 'aguardando_aprovacao')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('payment_term_days')
                            ->label('Prazo do boleto')
                            ->options([
                                30 => '30 dias',
                                45 => '45 dias',
                                60 => '60 dias',
                            ])
                            ->required(),
                    ])
                    ->action(function (QuoteRequest $record, array $data) {
                        $term = (int) $data['payment_term_days'];

                        $record->forceFill([
                            'boleto_status' => 'aprovado',
                            'payment_term_days' => $term,
                            'due_date' => now()->addDays($term),
                        ])->save();
                    }),
                Action::make('rejeitarBoleto')
                    ->label('Rejeitar boleto')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QuoteRequest $record) => $record->payment_method === 'boleto' && $record->boleto_status === 'aguardando_aprovacao')
                    ->requiresConfirmation()
                    ->action(fn (QuoteRequest $record) => $record->forceFill(['boleto_status' => 'rejeitado'])->save()),
                Action::make('marcarEmitido')
                    ->label('Marcar boleto emitido')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (QuoteRequest $record) => $record->boleto_status === 'aprovado')
                    ->action(fn (QuoteRequest $record) => $record->forceFill(['boleto_status' => 'emitido'])->save()),
                Action::make('marcarPago')
                    ->label('Marcar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (QuoteRequest $record) => $record->boleto_status === 'emitido')
                    ->action(fn (QuoteRequest $record) => $record->forceFill(['boleto_status' => 'pago'])->save()),
                Action::make('marcarAtendido')
                    ->label('Marcar atendido')
                    ->icon('heroicon-o-check')
                    ->visible(fn (QuoteRequest $record) => $record->status === 'novo')
                    ->action(fn (QuoteRequest $record) => $record->forceFill(['status' => 'atendido'])->save()),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
