<?php

namespace Tests\Feature;

use App\Mail\NewCreditApplicationAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreditApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_renders(): void
    {
        $this->get('/ficha-cadastral')
            ->assertOk()
            ->assertSee('Ficha cadastral B2B');
    }

    public function test_submission_creates_pending_application_and_sends_alert(): void
    {
        Mail::fake();

        $response = $this->post('/ficha-cadastral', [
            'company_name' => 'Indústria Exemplo Ltda',
            'document' => '11.222.333/0001-81',
            'contact_name' => 'Maria Compradora',
            'phone' => '41999998888',
            'email' => 'maria@exemplo.com',
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        $response->assertRedirect(route('credit-application.create'));

        $this->assertDatabaseHas('credit_applications', [
            'company_name' => 'Indústria Exemplo Ltda',
            'document' => '11222333000181',
            'status' => 'pendente',
        ]);

        Mail::assertSent(NewCreditApplicationAlert::class, function (NewCreditApplicationAlert $mail) {
            return $mail->creditApplication->company_name === 'Indústria Exemplo Ltda';
        });
    }

    public function test_invalid_cnpj_is_rejected(): void
    {
        $this->from('/ficha-cadastral')
            ->post('/ficha-cadastral', [
                'company_name' => 'Indústria Exemplo Ltda',
                'document' => '11.111.111/1111-11',
                'contact_name' => 'Maria Compradora',
                'phone' => '41999998888',
            ])
            ->assertSessionHasErrors('document');
    }
}
