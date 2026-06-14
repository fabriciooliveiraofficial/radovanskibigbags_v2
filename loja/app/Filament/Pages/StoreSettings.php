<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StoreSettings extends Page
{
    protected string $view = 'filament.pages.store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Dados da loja e frete';

    protected static ?string $title = 'Configurações da loja';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public const KEYS = [
        'store_name', 'store_cnpj', 'store_whatsapp', 'store_email', 'store_address',
        'store_cep', 'store_city', 'store_hours', 'store_instagram', 'store_logo',
        'pickup_info',
        'shipping_origin_cep', 'shipping_price_per_km', 'shipping_min_fee',
        'shipping_max_radius_km', 'shipping_delivery_days',
        'melhorenvio_token', 'superfrete_token', 'frenet_token', 'openroute_api_key',
        'seo_home_title', 'seo_home_description',
    ];

    public function mount(): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = Setting::get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da empresa')
                    ->description('Usados no site, nos orçamentos e no SEO local (schema.org LocalBusiness).')
                    ->schema([
                        TextInput::make('store_name')->label('Nome da empresa')->default('Radovanski Big Bags'),
                        TextInput::make('store_cnpj')->label('CNPJ'),
                        TextInput::make('store_whatsapp')
                            ->label('WhatsApp (com DDD)')
                            ->helperText('Número que recebe os pedidos de orçamento. Ex: 41999999999'),
                        TextInput::make('store_email')->label('E-mail')->email(),
                        TextInput::make('store_address')->label('Endereço completo'),
                        TextInput::make('store_cep')->label('CEP'),
                        TextInput::make('store_city')->label('Cidade/UF')->default('Curitiba - PR'),
                        TextInput::make('store_hours')
                            ->label('Horário de atendimento')
                            ->helperText('Ex: Seg a Sex 8h–18h, Sáb 8h–12h'),
                        TextInput::make('store_instagram')->label('Instagram (URL)'),
                        FileUpload::make('store_logo')
                            ->label('Logo (substitui o padrão)')
                            ->image()
                            ->disk('public')
                            ->directory('loja'),
                        Textarea::make('pickup_info')
                            ->label('Instruções de retirada')
                            ->helperText('Aparece na página de retirada e nos orçamentos com modalidade "Retirada".')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Entrega própria (Curitiba e região)')
                    ->description('Cálculo por distância: CEP do cliente → distância em km → preço.')
                    ->schema([
                        TextInput::make('shipping_origin_cep')
                            ->label('CEP de origem (depósito)'),
                        TextInput::make('shipping_price_per_km')
                            ->label('Preço por km (R$)')
                            ->numeric(),
                        TextInput::make('shipping_min_fee')
                            ->label('Valor mínimo de entrega (R$)')
                            ->numeric(),
                        TextInput::make('shipping_max_radius_km')
                            ->label('Raio máximo de entrega (km)')
                            ->numeric()
                            ->helperText('Acima disso, o site sugere transportadora ou retirada.'),
                        TextInput::make('shipping_delivery_days')
                            ->label('Prazo padrão de entrega')
                            ->placeholder('1 a 2 dias úteis'),
                    ])
                    ->columns(3),

                Section::make('APIs de frete (transportadora)')
                    ->description('Cotação automática com fallback: Melhor Envio → SuperFrete → Frenet. Tokens gratuitos — instruções no guia de implantação.')
                    ->schema([
                        TextInput::make('melhorenvio_token')->label('Token Melhor Envio')->password()->revealable(),
                        TextInput::make('superfrete_token')->label('Token SuperFrete')->password()->revealable(),
                        TextInput::make('frenet_token')->label('Token Frenet')->password()->revealable(),
                        TextInput::make('openroute_api_key')
                            ->label('Chave OpenRouteService (distância km)')
                            ->helperText('Gratuita em openrouteservice.org — usada na entrega própria.')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(2),

                Section::make('SEO da página inicial')
                    ->schema([
                        TextInput::make('seo_home_title')
                            ->label('Título da home')
                            ->placeholder('Big Bags em Curitiba — Novos, Lavados e Usados | Radovanski')
                            ->maxLength(70),
                        Textarea::make('seo_home_description')
                            ->label('Descrição da home')
                            ->placeholder('Big bags novos, lavados e usados e sacos de ráfia em Curitiba. Atendimento direto pelo WhatsApp, retirada no local e entrega na região.')
                            ->maxLength(320)
                            ->rows(2),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::set($key, is_array($value) ? json_encode($value) : $value);
        }

        Notification::make()
            ->title('Configurações salvas')
            ->success()
            ->send();
    }
}
