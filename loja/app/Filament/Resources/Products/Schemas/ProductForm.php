<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Produto')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Dados gerais')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome do produto')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null)
                                    ->columnSpan(2),
                                TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->helperText('Endereço da página do produto. Ex: big-bag-novo-90x90x120')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('category_id')
                                    ->label('Categoria')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable(),
                                Select::make('condition')
                                    ->label('Condição')
                                    ->options(Product::CONDITIONS)
                                    ->required(),
                                TextInput::make('sku')
                                    ->label('Código (SKU)'),
                                Textarea::make('short_description')
                                    ->label('Descrição curta')
                                    ->helperText('Aparece na listagem de produtos. Máx. 500 caracteres.')
                                    ->maxLength(500)
                                    ->rows(2)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label('Descrição completa')
                                    ->columnSpanFull(),
                                Select::make('useCases')
                                    ->label('Usos recomendados')
                                    ->helperText('Alimenta os filtros e o assistente "Não sei a medida"')
                                    ->relationship('useCases', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->label('Produto ativo (visível no site)')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Produto em destaque'),
                                TextInput::make('sort_order')
                                    ->label('Ordem de exibição')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3),

                        Tab::make('Especificações')
                            ->schema([
                                Section::make('Medidas e capacidade')
                                    ->schema([
                                        TextInput::make('width_cm')->label('Largura (cm)')->numeric(),
                                        TextInput::make('depth_cm')->label('Comprimento (cm)')->numeric(),
                                        TextInput::make('height_cm')->label('Altura (cm)')->numeric(),
                                        TextInput::make('capacity_kg')->label('Capacidade (kg)')->numeric(),
                                        TextInput::make('weight_kg')
                                            ->label('Peso do item (kg)')
                                            ->helperText('Usado no cálculo de frete')
                                            ->numeric(),
                                    ])
                                    ->columns(5),
                                Section::make('Características')
                                    ->schema([
                                        Toggle::make('has_discharge_valve')->label('Válvula de descarga'),
                                        Toggle::make('has_liner')->label('Liner interno'),
                                        TextInput::make('loops_count')->label('Quantidade de alças')->numeric(),
                                        Select::make('top_type')
                                            ->label('Parte superior')
                                            ->options([
                                                'aberto' => 'Aberto (boca cheia)',
                                                'valvula' => 'Válvula de enchimento',
                                                'saia' => 'Saia',
                                            ]),
                                        Select::make('bottom_type')
                                            ->label('Fundo')
                                            ->options([
                                                'fechado' => 'Fechado',
                                                'valvula' => 'Válvula de descarga',
                                            ]),
                                    ])
                                    ->columns(3),
                                Section::make('Atributos extras (filtros personalizados)')
                                    ->description('Crie atributos no menu "Atributos" e atribua valores aqui — eles viram filtros no site automaticamente.')
                                    ->schema([
                                        Repeater::make('attributeValues')
                                            ->hiddenLabel()
                                            ->relationship('attributeValues')
                                            ->schema([
                                                Select::make('attribute_id')
                                                    ->label('Atributo')
                                                    ->relationship('attribute', 'name')
                                                    ->required(),
                                            ])
                                            ->columns(1)
                                            ->defaultItems(0)
                                            ->addActionLabel('Adicionar atributo'),
                                    ]),
                            ]),

                        Tab::make('Preço e estoque')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Preço unitário (R$)')
                                    ->numeric()
                                    ->prefix('R$'),
                                Toggle::make('price_visible')
                                    ->label('Exibir preço no site')
                                    ->helperText('Desligado: o site mostra "Sob consulta" no lugar do preço.')
                                    ->default(true),
                                TextInput::make('min_order_qty')
                                    ->label('Pedido mínimo (unidades)')
                                    ->numeric()
                                    ->default(1),
                                TextInput::make('unit')
                                    ->label('Unidade de venda')
                                    ->default('un')
                                    ->helperText('un, fardo, pacote...'),
                                TextInput::make('stock_quantity')
                                    ->label('Estoque disponível')
                                    ->helperText('Deixe vazio para não controlar estoque.')
                                    ->numeric(),
                                Select::make('availability')
                                    ->label('Disponibilidade')
                                    ->options([
                                        'disponivel' => 'Disponível',
                                        'sob_consulta' => 'Sob consulta',
                                        'esgotado' => 'Esgotado',
                                    ])
                                    ->default('disponivel'),
                            ])
                            ->columns(2),

                        Tab::make('Vídeo')
                            ->schema([
                                TextInput::make('video_url')
                                    ->label('URL do vídeo (YouTube)')
                                    ->helperText('Cole o link do YouTube. As fotos são gerenciadas na seção "Fotos", abaixo do formulário, após salvar o produto.')
                                    ->url()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Título SEO')
                                    ->helperText('Aparece na aba do navegador e no Google. Se vazio, usa o nome do produto. Inclua "Curitiba" quando fizer sentido.')
                                    ->maxLength(70),
                                Textarea::make('seo_description')
                                    ->label('Descrição SEO (meta description)')
                                    ->helperText('Resumo de até 160 caracteres exibido no Google.')
                                    ->maxLength(320)
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }
}
