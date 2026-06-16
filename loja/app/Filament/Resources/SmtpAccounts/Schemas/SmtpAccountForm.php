<?php

namespace App\Filament\Resources\SmtpAccounts\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SmtpAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Servidor de saída (SMTP)')
                ->description('Configurações do servidor de e-mail que enviará as mensagens.')
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome do perfil')
                        ->placeholder('Ex: Hostinger — fabricio')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('host')
                        ->label('Servidor SMTP')
                        ->placeholder('smtp.hostinger.com')
                        ->required()
                        ->default('smtp.hostinger.com'),

                    TextInput::make('port')
                        ->label('Porta')
                        ->numeric()
                        ->required()
                        ->default(465),

                    Select::make('encryption')
                        ->label('Criptografia')
                        ->options([
                            'ssl' => 'SSL (porta 465)',
                            'tls' => 'TLS/STARTTLS (porta 587)',
                        ])
                        ->default('ssl')
                        ->required(),

                    TextInput::make('username')
                        ->label('Usuário / e-mail de login')
                        ->placeholder('fabricio@radovanskibigbags.com.br')
                        ->email()
                        ->required(),

                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->revealable()
                        ->required()
                        ->dehydrateStateUsing(fn ($state) => $state)
                        ->columnSpan(2),
                ]),

            Section::make('Remetente (De:)')
                ->columns(2)
                ->schema([
                    TextInput::make('from_address')
                        ->label('E-mail do remetente')
                        ->placeholder('fabricio@radovanskibigbags.com.br')
                        ->email()
                        ->required(),

                    TextInput::make('from_name')
                        ->label('Nome do remetente')
                        ->placeholder('Radovanski Big Bags'),
                ]),

            Section::make('Opções')
                ->columns(2)
                ->schema([
                    Toggle::make('is_default')
                        ->label('Perfil padrão')
                        ->helperText('Todos os e-mails do sistema usarão este perfil. Só um perfil pode ser o padrão.')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $get, $record) {
                            if ($state && $record) {
                                \App\Models\SmtpAccount::where('id', '!=', $record->id)
                                    ->where('is_default', true)
                                    ->update(['is_default' => false]);
                            }
                        }),

                    Toggle::make('is_active')
                        ->label('Ativo')
                        ->default(true),

                    Placeholder::make('spf_tip')
                        ->label('⚠️ Dica de entregabilidade')
                        ->content('Para evitar que os e-mails caiam no spam, configure SPF, DKIM e DMARC no DNS do seu domínio na Hostinger. Acesse o painel Hostinger → DNS → adicione o registro SPF: v=spf1 include:_spf.hostinger.com ~all')
                        ->columnSpanFull(),
                ]),

        ]);
    }
}
