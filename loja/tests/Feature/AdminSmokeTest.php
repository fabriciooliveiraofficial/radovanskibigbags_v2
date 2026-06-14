<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_dashboard_renders(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_resource_index_pages_render(): void
    {
        $admin = $this->admin();

        foreach ([
            '/admin/products',
            '/admin/categories',
            '/admin/customers',
            '/admin/quotes',
            '/admin/payment-methods',
            '/admin/faqs',
            '/admin/attributes',
            '/admin/use-cases',
            '/admin/store-settings',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_resource_create_pages_render(): void
    {
        $admin = $this->admin();

        foreach ([
            '/admin/products/create',
            '/admin/categories/create',
            '/admin/customers/create',
            '/admin/quotes/create',
            '/admin/payment-methods/create',
            '/admin/faqs/create',
            '/admin/attributes/create',
            '/admin/use-cases/create',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_product_edit_page_renders(): void
    {
        $category = Category::create(['name' => 'Big Bags Novos', 'slug' => 'big-bags-novos']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Big Bag Novo 90x90x120',
            'slug' => 'big-bag-novo-90x90x120',
            'condition' => 'novo',
            'price' => 45.90,
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk();
    }

    public function test_quote_edit_page_renders_and_totals_calculate(): void
    {
        $customer = Customer::create(['name' => 'Indústria Teste', 'phone' => '41999999999']);
        $quote = Quote::create(['customer_id' => $customer->id]);

        $quote->items()->create([
            'description' => 'Big Bag Novo 90x90x120',
            'qty' => 10,
            'unit_price' => 45.90,
            'total' => 0, // recalculado no saving()
        ]);

        $quote->refresh();

        $this->assertSame('459.00', (string) $quote->subtotal);
        $this->assertSame('459.00', (string) $quote->total);
        $this->assertNotNull($quote->number);
        $this->assertNotNull($quote->public_token);

        $this->actingAs($this->admin())
            ->get("/admin/quotes/{$quote->id}/edit")
            ->assertOk();
    }
}
