<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPendingQuotes extends BaseWidget
{
    protected static ?string $heading = 'Orçamentos Aguardando Resposta (Follow-up)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quote::where('type', 'orcamento')
                    ->whereIn('status', ['enviado', 'visualizado'])
                    ->latest('updated_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('number')
                    ->label('Nº Orçamento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.company')
                    ->label('Empresa')
                    ->description(fn (Quote $record) => $record->customer?->name)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Quote::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'enviado'               => 'info',
                        'visualizado'           => 'warning',
                        default                 => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL'),
                TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('viewed_at')
                    ->label('Visualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('abrir')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Quote $record) => $record->publicUrl(), shouldOpenInNewTab: true),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (Quote $record) {
                        $phone = $record->customer?->whatsappNumber();
                        if (empty($phone) || $phone === '55') {
                            return null;
                        }

                        $text = urlencode(implode("\n", [
                            "Olá, tudo bem?",
                            "Estou entrando em contato sobre o orçamento *{$record->number}* que enviamos para a *{$record->customer?->company}*.",
                            "Você conseguiu analisar a proposta?",
                            "Segue o link para visualização: " . $record->publicUrl(),
                        ]));

                        return "https://wa.me/{$phone}?text={$text}";
                    }, shouldOpenInNewTab: true)
                    ->visible(fn (Quote $record) => !empty($record->customer?->phone)),
                Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Quote $record) => \App\Filament\Resources\Quotes\QuoteResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
