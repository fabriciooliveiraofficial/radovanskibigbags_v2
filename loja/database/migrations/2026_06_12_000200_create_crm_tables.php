<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('document', 20)->nullable(); // CNPJ/CPF
            $table->string('phone', 20); // WhatsApp
            $table->string('email')->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->default('Curitiba');
            $table->string('state', 2)->default('PR');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();        // ORC-2026-0001
            $table->string('public_token', 32)->unique(); // link público
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('rascunho'); // rascunho | enviado | visualizado | aprovado | recusado | expirado
            $table->date('valid_until')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type')->nullable(); // percent | fixed
            $table->decimal('discount_value', 12, 2)->default(0);

            // Frete / retirada
            $table->string('shipping_method')->default('retirada'); // retirada | entrega_propria | transportadora
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('shipping_deadline')->nullable(); // "1 a 2 dias úteis"
            $table->string('shipping_carrier')->nullable();

            $table->decimal('total', 12, 2)->default(0);

            $table->string('payment_terms')->nullable(); // "PIX na retirada", "10x sem juros"...
            $table->text('notes')->nullable();           // observações visíveis ao cliente
            $table->text('internal_notes')->nullable();  // anotações internas

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('sent_channels')->nullable(); // ["whatsapp","email"...]

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem criou
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description'); // congela o nome no momento do orçamento
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 12, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Rastreamento: criado, enviado, visualizado, aprovado...
        Schema::create('quote_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // created | sent | viewed | approved | rejected
            $table->json('meta')->nullable(); // canal, IP, user-agent
            $table->timestamp('created_at')->useCurrent();
        });

        // Pedidos de orçamento vindos do site (carrinho-cotação → WhatsApp)
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('city')->nullable();
            $table->json('items'); // [{product_id, variant_id, qty}]
            $table->string('status')->default('novo'); // novo | atendido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
        Schema::dropIfExists('quote_events');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('customers');
    }
};
