<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuoteEmail;
use Filament\Notifications\Notification;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->description(fn (Quote $record) => $record->customer?->company)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Quote::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'rascunho' => 'gray',
                        'enviado' => 'info',
                        'visualizado' => 'warning',
                        'aprovado' => 'success',
                        'recusado', 'expirado' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Válido até')
                    ->date('d/m/Y'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Quote::STATUSES),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Ver orçamento')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Quote $record) => $record->publicUrl(), shouldOpenInNewTab: true),
                Action::make('enviarEmail')
                    ->label('Enviar por E-mail')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        TextInput::make('email')
                            ->label('E-mail do cliente')
                            ->email()
                            ->required()
                            ->default(fn (Quote $record) => $record->customer?->email),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $email = $data['email'];

                        if ($record->customer && $record->customer->email !== $email) {
                            $record->customer->forceFill(['email' => $email])->save();
                        }

                        Mail::to($email)->send(new QuoteEmail($record));

                        if ($record->status === 'rascunho') {
                            $record->forceFill(['status' => 'enviado', 'sent_at' => now()])->saveQuietly();
                        }

                        $record->events()->create([
                            'type' => 'sent',
                            'meta' => ['channel' => 'email', 'to' => $email]
                        ]);

                        Notification::make()
                            ->title('Orçamento enviado')
                            ->description("Enviado com sucesso para {$email}.")
                            ->success()
                            ->send();
                    }),
                Action::make('enviado')
                    ->label('Marcar enviado')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Quote $record) => $record->status === 'rascunho')
                    ->action(function (Quote $record) {
                        $record->forceFill(['status' => 'enviado', 'sent_at' => now()])->saveQuietly();
                        $record->events()->create(['type' => 'sent']);
                    }),
                Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar orçamento')
                    ->modalDescription('Cria um novo orçamento (rascunho) com os mesmos itens e condições.')
                    ->action(function (Quote $record) {
                        $new = $record->replicate(['number', 'public_token', 'status', 'sent_at', 'viewed_at', 'approved_at', 'sent_channels']);
                        $new->status = 'rascunho';
                        $new->valid_until = now()->addDays(7);
                        $new->save();

                        foreach ($record->items as $item) {
                            $new->items()->create($item->only(['product_id', 'product_variant_id', 'description', 'qty', 'unit_price', 'total', 'sort_order']));
                        }

                        $new->events()->create(['type' => 'created', 'meta' => ['duplicated_from' => $record->number]]);

                        return redirect(\App\Filament\Resources\Quotes\QuoteResource::getUrl('edit', ['record' => $new]));
                    }),
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
