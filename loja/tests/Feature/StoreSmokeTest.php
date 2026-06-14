<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_home_renders_with_categories_and_featured(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Big Bags em Curitiba')
            ->assertSee('Big Bags Novos')
            ->assertSee('Como funciona');
    }

    public function test_catalog_renders_and_filters_work(): void
    {
        $this->get('/produtos')->assertOk()->assertSee('Big Bag Novo 90×90×120');

        // Filtro por condição
        $this->get('/produtos?condicao[]=sujo')
            ->assertOk()
            ->assertSee('Big Bag Sujo')
            ->assertDontSee('Big Bag Novo 90×90×120 — 1.000 kg');

        // Filtro por capacidade
        $this->get('/produtos?capacidade=acima-1500')
            ->assertOk()
            ->assertSee('1.500 kg');

        // Busca
        $this->get('/produtos?q=ráfia')
            ->assertOk()
            ->assertSee('Saco de Ráfia');

        // Filtro por válvula
        $this->get('/produtos?valvula=1')
            ->assertOk()
            ->assertSee('Válvula');
    }

    public function test_product_page_renders_with_schema_and_price(): void
    {
        $this->get('/produto/big-bag-novo-90x90x120-1000kg')
            ->assertOk()
            ->assertSee('R$ 42,90')
            ->assertSee('Especificações')
            ->assertSee('schema.org', escape: false)
            ->assertSee('Adicionar à cotação');
    }

    public function test_product_with_hidden_price_shows_sob_consulta(): void
    {
        $this->get('/produto/big-bag-sujo-grande-1500kg')
            ->assertOk()
            ->assertSee('Sob consulta');
    }

    public function test_category_landing_page_renders(): void
    {
        $this->get('/big-bags-lavados-curitiba')
            ->assertOk()
            ->assertSee('Big Bags Lavados em Curitiba');
    }

    public function test_use_case_landing_page_renders(): void
    {
        $this->get('/big-bags-para-reciclagem')
            ->assertOk()
            ->assertSee('Big Bags para Reciclagem em Curitiba');
    }

    public function test_wizard_recommends_products(): void
    {
        $this->get('/assistente-de-medidas?uso=reciclagem&peso=500-1000&condicao=economico')
            ->assertOk()
            ->assertSee('Recomendados para você');
    }

    public function test_cart_flow_and_whatsapp_redirect(): void
    {
        $product = Product::where('slug', 'big-bag-lavado-90x90x120-1000kg')->firstOrFail();

        // Adiciona
        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 20])
            ->assertRedirect(route('cart.index'));

        // Carrinho mostra item e estimativa
        $this->get('/cotacao')
            ->assertOk()
            ->assertSee('Big Bag Lavado')
            ->assertSee('Pedir orçamento no WhatsApp');

        // Envia para WhatsApp: registra QuoteRequest e redireciona para wa.me
        $response = $this->post('/cotacao/whatsapp', ['name' => 'João', 'city' => 'Curitiba']);
        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/5541999999999', $response->headers->get('Location'));
        $this->assertDatabaseHas('quote_requests', ['name' => 'João', 'city' => 'Curitiba']);

        // Carrinho limpo após envio
        $this->get('/cotacao')->assertSee('Sua lista está vazia');
    }

    public function test_bulk_pricing_applied_in_cart(): void
    {
        $product = Product::where('slug', 'big-bag-sujo-1000kg')->firstOrFail();

        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 150]);

        // 150 un cai na faixa "a partir de 100" = R$ 10,90
        $this->get('/cotacao')->assertSee('R$ 10,90');
    }

    public function test_public_quote_page_tracks_view_and_approval(): void
    {
        $customer = Customer::create(['name' => 'Recicla Sul Ltda', 'phone' => '41988887777']);
        $quote = Quote::create(['customer_id' => $customer->id, 'status' => 'enviado', 'valid_until' => now()->addDays(7)]);
        $quote->items()->create(['description' => 'Big Bag Lavado', 'qty' => 100, 'unit_price' => 19.90, 'total' => 0]);

        // Visualização marca status
        $this->get('/orcamento/'.$quote->public_token)
            ->assertOk()
            ->assertSee($quote->number)
            ->assertSee('APROVAR AGORA');

        $quote->refresh();
        $this->assertSame('visualizado', $quote->status);
        $this->assertNotNull($quote->viewed_at);

        // Aprovação redireciona para WhatsApp da loja
        $response = $this->post('/orcamento/'.$quote->public_token.'/aprovar');
        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/5541999999999', $response->headers->get('Location'));

        $quote->refresh();
        $this->assertSame('aprovado', $quote->status);
        $this->assertNotNull($quote->approved_at);
    }

    public function test_quote_pdf_downloads(): void
    {
        $customer = Customer::create(['name' => 'Agro Teste', 'phone' => '41977776666']);
        $quote = Quote::create(['customer_id' => $customer->id]);
        $quote->items()->create(['description' => 'Big Bag Novo', 'qty' => 50, 'unit_price' => 42.90, 'total' => 0]);

        $this->get('/orcamento/'.$quote->public_token.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_supporting_pages_render(): void
    {
        $this->get('/retirada')->assertOk()->assertSee('Retirada e pagamento');
        $this->get('/perguntas-frequentes')->assertOk()->assertSee('FAQPage', escape: false);
    }

    public function test_sitemap_lists_products_and_landings(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml')
            ->assertSee('big-bag-novo-90x90x120-1000kg')
            ->assertSee('big-bags-para-reciclagem');
    }
}
