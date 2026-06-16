<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Models\Quote;
use App\Models\SmtpAccount;
use App\Services\SmtpMailService;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'pedido' ? 'Pedido' : 'Orçamento')
                    ->color(fn (string $state) => $state === 'pedido' ? 'success' : 'gray'),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->description(fn (Quote $record) => $record->customer?->company)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Quote::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'rascunho'              => 'gray',
                        'enviado'               => 'info',
                        'visualizado'           => 'warning',
                        'aprovado'              => 'success',
                        'recusado', 'expirado'  => 'danger',
                        default                 => 'gray',
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
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'orcamento' => 'Orçamento',
                        'pedido'    => 'Pedido',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Quote::STATUSES),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Quote $record) => $record->publicUrl(), shouldOpenInNewTab: true),

                Action::make('enviarEmail')
                    ->label('Enviar por e-mail')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->form(function (Quote $record) {
                        $smtpOptions = SmtpAccount::where('is_active', true)->pluck('name', 'id')->toArray();
                        $defaultId   = SmtpAccount::where('is_default', true)->value('id');
                        $tipo        = $record->isPedido() ? 'pedido' : 'orçamento';

                        return [
                            Select::make('smtp_account_id')
                                ->label('Servidor de e-mail')
                                ->options($smtpOptions)
                                ->default($defaultId)
                                ->placeholder('Nenhum SMTP configurado')
                                ->searchable(),

                            TextInput::make('to')
                                ->label('Para (destinatários)')
                                ->placeholder('cliente@empresa.com, nfe@empresa.com')
                                ->helperText('Separe múltiplos e-mails por vírgula ou ponto-e-vírgula.')
                                ->required()
                                ->default($record->customer?->email ?? ''),

                            TextInput::make('cc')
                                ->label('CC (com cópia, opcional)')
                                ->placeholder('outro@empresa.com'),

                            TextInput::make('subject')
                                ->label('Assunto')
                                ->default(ucfirst($tipo).' '.$record->number.' — '.store_setting('store_name', 'Radovanski Big Bags'))
                                ->required(),

                            Textarea::make('body')
                                ->label('Mensagem')
                                ->rows(5)
                                ->default(implode("\n", [
                                    'Olá!',
                                    '',
                                    'Segue o link do seu '.($record->isPedido() ? 'pedido' : 'orçamento').':',
                                    $record->publicUrl(),
                                    '',
                                    $record->isPedido()
                                        ? 'Para confirmar, acesse o link acima e clique em "Confirmar Pedido".'
                                        : 'Para aprovar, acesse o link acima.',
                                    '',
                                    'Dúvidas? Fale pelo WhatsApp: '.store_setting('store_whatsapp', ''),
                                    '',
                                    'Atenciosamente,',
                                    store_setting('store_name', 'Radovanski Big Bags'),
                                ]))
                                ->required(),

                            Toggle::make('attach_pdf')
                                ->label('Anexar PDF')
                                ->default(true),
                        ];
                    })
                    ->action(function (Quote $record, array $data) {
                        $toRaw = preg_split('/[,;]+/', $data['to']);
                        $to    = array_values(array_filter(array_map('trim', $toRaw)));
                        $cc    = [];
                        if (! empty($data['cc'])) {
                            $ccRaw = preg_split('/[,;]+/', $data['cc']);
                            $cc    = array_values(array_filter(array_map('trim', $ccRaw)));
                        }

                        $account = $data['smtp_account_id']
                            ? SmtpAccount::find($data['smtp_account_id'])
                            : SmtpAccount::default();

                        $log = app(SmtpMailService::class)->send(
                            quote: $record,
                            to: $to,
                            subject: $data['subject'],
                            body: nl2br(e($data['body'])),
                            attachPdf: (bool) ($data['attach_pdf'] ?? false),
                            cc: $cc,
                            account: $account,
                        );

                        if ($record->status === 'rascunho') {
                            $record->forceFill(['status' => 'enviado', 'sent_at' => now()])->saveQuietly();
                        }

                        $record->events()->create([
                            'type' => 'sent',
                            'meta' => ['channel' => 'email', 'to' => $to],
                        ]);

                        if ($log->status === 'falhou') {
                            Notification::make()
                                ->title('❌ Falha ao enviar e-mail')
                                ->body($log->error)
                                ->danger()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('✅ E-mail enviado com sucesso!')
                                ->body('Enviado para: '.implode(', ', $to))
                                ->success()
                                ->send();
                        }
                    }),

                Action::make('enviado')
                    ->label('Marcar enviado')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Quote $record) => $record->status === 'rascunho')
                    ->action(function (Quote $record) {
                        $record->forceFill(['status' => 'enviado', 'sent_at' => now()])->saveQuietly();
                        $record->events()->create(['type' => 'sent']);
                    }),

                Action::make('converterEmPedido')
                    ->label('Converter em Pedido')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Quote $record) => $record->type === 'orcamento')
                    ->requiresConfirmation()
                    ->modalHeading('Converter Orçamento em Pedido')
                    ->modalDescription('O orçamento receberá um número de pedido (OC-XXXXXXX). Todos os itens, cliente, frete e condições são mantidos. Esta ação não pode ser desfeita.')
                    ->action(function (Quote $record) {
                        // Mantém o mesmo número: ORC-0001606 → OC-0001606
                        $numeroPedido = 'OC-'.Str::afterLast($record->number, '-');

                        $record->forceFill([
                            'type'   => 'pedido',
                            'number' => $numeroPedido,
                        ])->save();

                        $record->events()->create([
                            'type' => 'converted',
                            'meta' => ['from_type' => 'orcamento', 'orc_number' => $record->getOriginal('number')],
                        ]);

                        Notification::make()
                            ->title('Pedido '.$numeroPedido.' gerado!')
                            ->body('Orçamento convertido. Todos os dados foram mantidos.')
                            ->success()
                            ->send();
                    }),

                Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar documento')
                    ->modalDescription('Cria um novo rascunho com os mesmos itens e condições.')
                    ->action(function (Quote $record) {
                        $new = $record->replicate(['number', 'public_token', 'status', 'sent_at', 'viewed_at', 'approved_at', 'sent_channels', 'quote_request_id']);
                        $new->status     = 'rascunho';
                        $new->valid_until = now()->addDays(7);
                        $new->save();

                        foreach ($record->items as $item) {
                            $new->items()->create(
                                $item->only(['product_id', 'product_variant_id', 'description', 'qty', 'unit_price', 'weight_kg', 'total', 'sort_order'])
                            );
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
