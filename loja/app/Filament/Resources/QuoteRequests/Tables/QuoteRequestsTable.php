<?php

namespace App\Filament\Resources\QuoteRequests\Tables;

use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\SmtpAccount;
use App\Services\OrderGenerator;
use App\Services\SmtpMailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                        'aprovado'             => 'success',
                        'rejeitado'            => 'danger',
                        'emitido'              => 'info',
                        'pago'                 => 'success',
                        default                => 'gray',
                    }),
                TextColumn::make('quote.number')
                    ->label('Pedido gerado')
                    ->placeholder('—')
                    ->badge()
                    ->color('success')
                    ->url(fn (QuoteRequest $record) => $record->quote
                        ? route('quote.public', $record->quote->public_token)
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => QuoteRequest::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'novo'     => 'warning',
                        'atendido' => 'success',
                        default    => 'gray',
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
                        'boleto'   => 'Boleto',
                    ]),
            ])
            ->recordActions([
                Action::make('gerarPedido')
                    ->label('Gerar Pedido')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn (QuoteRequest $record) => $record->quote === null)
                    ->form([
                        TextInput::make('cep')
                            ->label('CEP do cliente (para calcular frete)')
                            ->placeholder('00000-000')
                            ->helperText('Deixe em branco para usar Retirada no local')
                            ->maxLength(9),
                        TextInput::make('delivery_days')
                            ->label('Prazo de entrega (dias)')
                            ->numeric()
                            ->placeholder('Ex: 3'),
                        TextInput::make('notes')
                            ->label('Observações (visíveis no pedido)')
                            ->maxLength(500),
                    ])
                    ->action(function (QuoteRequest $record, array $data) {
                        $generator = app(OrderGenerator::class);
                        $quote = $generator->fromQuoteRequest($record, [
                            'cep'           => $data['cep'] ?? null,
                            'delivery_days' => $data['delivery_days'] ? (int) $data['delivery_days'] : null,
                            'notes'         => $data['notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Pedido '.$quote->number.' gerado com sucesso!')
                            ->body('Acesse: '.$quote->publicUrl())
                            ->success()
                            ->send();
                    }),

                Action::make('verPedido')
                    ->label('Ver Pedido')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->visible(fn (QuoteRequest $record) => $record->quote !== null)
                    ->url(fn (QuoteRequest $record) => $record->quote
                        ? route('quote.public', $record->quote->public_token)
                        : null)
                    ->openUrlInNewTab(),

                Action::make('enviarEmail')
                    ->label('Enviar por e-mail')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (QuoteRequest $record) => $record->quote !== null)
                    ->form(function (QuoteRequest $record) {
                        $smtpOptions = SmtpAccount::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                        $defaultId   = SmtpAccount::where('is_default', true)->value('id');

                        return [
                            Select::make('smtp_account_id')
                                ->label('Servidor de e-mail')
                                ->options($smtpOptions)
                                ->default($defaultId)
                                ->placeholder('Nenhum SMTP configurado')
                                ->helperText('Configure em Configurações → Servidores de E-mail (SMTP)')
                                ->searchable(),

                            TextInput::make('to')
                                ->label('Para (destinatários)')
                                ->placeholder('vitor@empresa.com, nfe@empresa.com, contato@empresa.com')
                                ->helperText('Separe múltiplos e-mails por vírgula ou ponto-e-vírgula.')
                                ->required()
                                ->default(fn () => $record->creditApplication?->email ?? ''),

                            TextInput::make('cc')
                                ->label('CC (com cópia, opcional)')
                                ->placeholder('outro@empresa.com'),

                            TextInput::make('subject')
                                ->label('Assunto')
                                ->default(fn () => 'Pedido '.$record->quote?->number.' — '.store_setting('store_name', 'Radovanski Big Bags'))
                                ->required(),

                            Textarea::make('body')
                                ->label('Mensagem')
                                ->rows(5)
                                ->default(fn () => implode("\n", [
                                    'Olá!',
                                    '',
                                    'Segue o link do seu pedido:',
                                    $record->quote?->publicUrl() ?? '',
                                    '',
                                    'Para confirmar, basta acessar o link acima e clicar em "Confirmar Pedido".',
                                    '',
                                    'Dúvidas? Responda este e-mail ou fale pelo WhatsApp: '.store_setting('store_whatsapp', ''),
                                    '',
                                    'Atenciosamente,',
                                    store_setting('store_name', 'Radovanski Big Bags'),
                                ]))
                                ->required(),

                            Toggle::make('attach_pdf')
                                ->label('Anexar PDF do pedido')
                                ->default(true),
                        ];
                    })
                    ->action(function (QuoteRequest $record, array $data) {
                        $quote = $record->quote;
                        if (! $quote) {
                            Notification::make()->title('Gere o pedido antes de enviar por e-mail.')->warning()->send();
                            return;
                        }

                        $toRaw = preg_split('/[,;]+/', $data['to']);
                        $to    = array_filter(array_map('trim', $toRaw));
                        $cc    = [];
                        if (! empty($data['cc'])) {
                            $ccRaw = preg_split('/[,;]+/', $data['cc']);
                            $cc    = array_filter(array_map('trim', $ccRaw));
                        }

                        $account = $data['smtp_account_id']
                            ? SmtpAccount::find($data['smtp_account_id'])
                            : SmtpAccount::default();

                        $body = nl2br(e($data['body']));

                        $log = app(SmtpMailService::class)->send(
                            quote: $quote,
                            to: array_values($to),
                            subject: $data['subject'],
                            body: $body,
                            attachPdf: (bool) ($data['attach_pdf'] ?? false),
                            cc: array_values($cc),
                            account: $account,
                        );

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
                                ->body('Enviado para: '.implode(', ', array_values($to)))
                                ->success()
                                ->send();
                        }
                    }),

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
                            'boleto_status'      => 'aprovado',
                            'payment_term_days'  => $term,
                            'due_date'           => now()->addDays($term),
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

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
