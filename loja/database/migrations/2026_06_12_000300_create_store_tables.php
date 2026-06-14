<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Formas de pagamento exibidas no site e nos orçamentos
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // PIX, Dinheiro, Cartão 10x...
            $table->string('description')->nullable(); // "10x sem juros no cartão"
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_quotes')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Configurações chave-valor da loja (dados da empresa, WhatsApp, frete...)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // FAQ (SEO: schema.org FAQPage + busca por voz)
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payment_methods');
    }
};
