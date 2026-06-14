<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Mail\QuoteEmail;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\QuoteApprovedAlert;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_cart_submission_sends_admin_alert(): void
    {
        Mail::fake();

        $product = Product::where('slug', 'big-bag-lavado-90x90x120-1000kg')->firstOrFail();

        // Adiciona ao carrinho
        $this->post('/cotacao/adicionar', ['product_id' => $product->id, 'qty' => 10])
            ->assertRedirect(route('cart.index'));

        // Finaliza cotação
        $response = $this->post('/cotacao/whatsapp', [
            'name' => 'João Mail Test',
            'phone' => '41999999999',
            'city' => 'Curitiba'
        ]);

        $response->assertRedirect();

        Mail::assertSent(NewQuoteRequestAlert::class, function (NewQuoteRequestAlert $mail) {
            return $mail->quoteRequest->name === 'João Mail Test' &&
                   $mail->quoteRequest->city === 'Curitiba';
        });
    }

    public function test_client_approval_sends_admin_alert(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'Aprovador de Orçamento', 'phone' => '41988887777']);
        $quote = Quote::create([
            'customer_id' => $customer->id, 
            'status' => 'enviado', 
            'valid_until' => now()->addDays(7)
        ]);

        $response = $this->post('/orcamento/'.$quote->public_token.'/aprovar');
        $response->assertRedirect();

        Mail::assertSent(QuoteApprovedAlert::class, function (QuoteApprovedAlert $mail) use ($quote) {
            return $mail->quote->id === $quote->id;
        });
    }

    public function test_mailables_content_and_attachments(): void
    {
        $customer = Customer::create(['name' => 'Comprador por Email', 'phone' => '41977776666', 'email' => 'old@example.com']);
        $quote = Quote::create([
            'customer_id' => $customer->id,
            'status' => 'rascunho',
            'valid_until' => now()->addDays(7)
        ]);

        // Adiciona itens ao orçamento
        $quote->items()->create(['description' => 'Big Bag Teste', 'qty' => 50, 'unit_price' => 42.90, 'total' => 2145.00]);

        $mailable = new QuoteEmail($quote);
        $mailable->assertHasSubject('Orçamento ' . $quote->number . ' — Radovanski Big Bags');
        $mailable->assertSeeInHtml($quote->number);
        
        $attachments = $mailable->attachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('orcamento-' . $quote->number . '.pdf', $attachments[0]->as);
    }
}
