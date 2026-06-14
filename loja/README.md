# Radovanski Big Bags — Loja Virtual

Loja virtual B2B de **big bags** (novos, lavados, sujos) e **sacos de ráfia** em Curitiba, focada em venda 100% pelo WhatsApp com retirada/pagamento presencial. Sem pagamento online.

**Stack:** Laravel 13 · Filament 5 (painel) · Livewire · Tailwind CSS 4 · MySQL (SQLite em dev).

---

## Funcionalidades

### Loja pública (estilo "cardápio")
- Catálogo em lista com cards foto + specs + preço + botão de cotação.
- **Filtros avançados**: categoria, condição, capacidade (kg), uso, válvula, faixa de preço e **atributos dinâmicos** (criados no painel).
- **Assistente "Não sei a medida"**: 3 perguntas → produtos recomendados.
- Página de produto com galeria, vídeo (YouTube), specs, preço por quantidade e produtos relacionados.
- **Carrinho-cotação** → gera mensagem pronta no WhatsApp (sem checkout).
- Cálculo de **frete** (retirada / entrega por km / transportadora) por CEP.
- Páginas de retirada e FAQ.

### Painel administrativo (`/admin`)
- **Produtos**: variações, fotos, vídeo, preço por quantidade, SEO, **preço visível ou "sob consulta"** por produto, duplicar.
- **Categorias, Usos, Atributos** dinâmicos (viram filtros e landing pages).
- **Orçamentos**: gerador completo → link público + PDF + imagem, compartilhamento (WhatsApp/Telegram/e-mail/Messenger/Web Share/QR), rastreamento de visualização e aprovação, duplicar.
- **Clientes, Formas de pagamento, FAQ**.
- **Configurações da loja**: dados da empresa, frete e tokens de API.
- Dashboard com métricas (orçamentos, taxa de aprovação, valor aprovado).

### SEO local (Curitiba)
- schema.org `LocalBusiness`, `Product`+`Offer`, `FAQPage`.
- Landing pages por categoria (`/big-bags-novos-curitiba`) e por uso (`/big-bags-para-reciclagem`).
- `sitemap.xml` automático, `robots.txt`, Open Graph, títulos/descrições por página.

---

## Rodar localmente

```bash
composer install
npm install && npm run build
cp .env.example .env   # já vem com SQLite
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder   # dados de exemplo
php artisan make:filament-user           # cria login do /admin
php artisan serve
```

- Loja: http://127.0.0.1:8000
- Painel: http://127.0.0.1:8000/admin

## Testes

```bash
php artisan test
```

Cobrem: páginas do painel, catálogo e filtros, preço visível/sob consulta, carrinho→WhatsApp, preço de atacado, orçamento público (view/aprovação/PDF), frete com fallback de APIs, sitemap.

## Deploy

Veja **[DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md)**.

---

## Estrutura principal

```
app/
  Filament/Resources/        # Produtos, Categorias, Orçamentos, Clientes...
  Filament/Pages/            # StoreSettings (configurações da loja)
  Filament/Widgets/          # Dashboard (StatsOverview)
  Http/Controllers/          # StoreController, CartController, QuotePublicController, SeoController
  Services/Cart.php          # carrinho-cotação em sessão
  Services/Shipping/         # FreightCalculator + DistanceService (frete com fallback)
  Models/                    # Product, Quote, Customer, Setting...
resources/views/
  layouts/store.blade.php    # layout da loja
  store/                     # home, products, product, wizard, cart, category, use-case, pickup, faq
  quote/                     # public (página do orçamento) + pdf
database/seeders/DemoSeeder.php
```
