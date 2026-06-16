<?php

namespace App\Filament\Resources\SmtpAccounts\Tables;

use App\Models\SmtpAccount;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SmtpAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Perfil')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('host')
                    ->label('Servidor')
                    ->formatStateUsing(fn (SmtpAccount $r) => $r->host.':'.$r->port.' ('.$r->encryption.')'),

                TextColumn::make('from_address')
                    ->label('Remetente')
                    ->searchable(),

                IconColumn::make('is_default')
                    ->label('Padrão')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('testarConexao')
                    ->label('Testar conexão')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (SmtpAccount $record) {
                        $key = 'smtp_test_'.$record->id;
                        config(["mail.mailers.{$key}" => $record->mailerConfig()]);

                        try {
                            Mail::mailer($key)
                                ->raw(
                                    'Teste de conexão SMTP — perfil "' .$record->name.'" funcionando corretamente.',
                                    function ($msg) use ($record) {
                                        $msg->from($record->from_address, $record->from_name ?? 'Radovanski Big Bags')
                                            ->to($record->from_address)
                                            ->subject('[Teste] Conexão SMTP — '.$record->name);
                                    }
                                );

                            Notification::make()
                                ->title('✅ Conexão bem-sucedida!')
                                ->body('E-mail de teste enviado para '.$record->from_address.'.')
                                ->success()
                                ->send();

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('❌ Falha na conexão SMTP')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('definirPadrao')
                    ->label('Definir padrão')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (SmtpAccount $record) => ! $record->is_default)
                    ->requiresConfirmation()
                    ->modalDescription('Todos os e-mails do sistema (alertas, pedidos, fichas cadastrais) passarão a usar este perfil.')
                    ->action(function (SmtpAccount $record) {
                        SmtpAccount::where('is_default', true)->update(['is_default' => false]);
                        $record->forceFill(['is_default' => true])->save();

                        Notification::make()
                            ->title('"'.$record->name.'" definido como perfil padrão.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->defaultSort('is_default', 'desc');
    }
}
