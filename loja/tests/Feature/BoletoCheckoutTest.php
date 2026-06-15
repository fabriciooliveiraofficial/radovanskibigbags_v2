<?php

namespace Tests\Feature;

use App\Models\CreditApplication;
use App\Models\Product;
use App\Models\QuoteRequest;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoletoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_approved_cnpj_unlocks_boleto_option(): void
    {
        $creditApplication = CreditApplication::create([
            'company_name' => 'Indústria Exemplo Ltda',
            'document' => '11222333000181',
            'contact_name' => 'Maria Compradora',
            'phone' => '41999998888',
            'status' => 'aprovado',
        ]);

        $product = Product::where('slug', 'big-bag-lavado-90x90x120-1000kg')->firstOrFail();

        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect(route('cart.index'));

        $this->post('/cotacao/boleto/verificar', ['cnpj' => '11.222.333/0001-81'])
            ->assertRedirect(route('cart.index'));

        $this->assertSame($creditApplication->id, (int) session('cart.credit_application_id'));

        $this->get('/cotacao')
            ->assertOk()
            ->assertSee('aprovada para pagamento com boleto');
    }

    public function test_unregistered_cnpj_does_not_unlock_boleto(): void
    {
        $product = Product::where('slug', 'big-bag-lavado-90x90x120-1000kg')->firstOrFail();

        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect(route('cart.index'));

        $this->post('/cotacao/boleto/verificar', ['cnpj' => '11.222.333/0001-81'])
            ->assertRedirect(route('cart.index'));

        $this->assertNull(session('cart.credit_application_id'));

        $this->get('/cotacao')
            ->assertOk()
            ->assertSee('Preencha a ficha cadastral');
    }

    public function test_full_boleto_checkout_flow(): void
    {
        $creditApplication = CreditApplication::create([
            'company_name' => 'Indústria Exemplo Ltda',
            'document' => '11222333000181',
            'contact_name' => 'Maria Compradora',
            'phone' => '41999998888',
            'status' => 'aprovado',
        ]);

        $product = Product::where('slug', 'big-bag-lavado-90x90x120-1000kg')->firstOrFail();

        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 10])
            ->assertRedirect(route('cart.index'));

        $this->post('/cotacao/boleto/verificar', ['cnpj' => '11.222.333/0001-81'])
            ->assertRedirect(route('cart.index'));

        $response = $this->post('/cotacao/whatsapp', [
            'name' => 'Maria',
            'city' => 'Curitiba',
            'payment_method' => 'boleto',
        ]);

        $response->assertRedirect();

        $quoteRequest = QuoteRequest::latest('id')->first();

        $this->assertSame('boleto', $quoteRequest->payment_method);
        $this->assertSame('aguardando_aprovacao', $quoteRequest->boleto_status);
        $this->assertSame($creditApplication->id, (int) $quoteRequest->credit_application_id);

        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertStringContainsString('💳 *Pagamento:* Boleto', $query['text']);
    }
}
