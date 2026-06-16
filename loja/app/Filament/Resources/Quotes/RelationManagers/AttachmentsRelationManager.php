<?php

namespace App\Filament\Resources\Quotes\RelationManagers;

use App\Models\QuoteAttachment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Anexos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Arquivo')
                ->disk('public')
                ->directory('quotes/attachments')
                ->preserveFilenames()
                ->maxSize(20480)
                ->acceptedFileTypes(['application/pdf', 'image/*', 'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain'])
                ->helperText('PDF, imagens, Excel, Word — máx. 20 MB.')
                ->columnSpanFull(),

            TextInput::make('label')
                ->label('Descrição (opcional)')
                ->placeholder('Ex: Boleto, NF-e, Contrato, Foto do produto')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->emptyStateHeading('Nenhum anexo')
            ->emptyStateDescription('Adicione boletos, notas fiscais, imagens ou qualquer documento relacionado.')
            ->columns([
                TextColumn::make('label')
                    ->label('Descrição')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('original_filename')
                    ->label('Arquivo')
                    ->formatStateUsing(fn (QuoteAttachment $record) => $record->typeIcon().' '.$record->original_filename),

                TextColumn::make('size_bytes')
                    ->label('Tamanho')
                    ->formatStateUsing(fn (QuoteAttachment $record) => $record->sizeFormatted()),

                TextColumn::make('created_at')
                    ->label('Adicionado em')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()->label('Adicionar anexo'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (QuoteAttachment $record) => $record->publicUrl())
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label('Renomear')
                    ->form([
                        TextInput::make('label')
                            ->label('Descrição')
                            ->placeholder('Ex: Boleto, NF-e, Contrato')
                            ->maxLength(255),
                    ]),

                Action::make('copiarLink')
                    ->label('Copiar link')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (QuoteAttachment $record) {
                        Notification::make()
                            ->title('Link copiado!')
                            ->body($record->publicUrl())
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()->requiresConfirmation(),
            ]);
    }
}
