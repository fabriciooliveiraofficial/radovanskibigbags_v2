<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorias (Big Bags Novos, Lavados, Sujos, Sacos de Ráfia...)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Usos recomendados (grãos, entulho, reciclagem...) — alimenta filtros,
        // o assistente de medidas e as landing pages de SEO por público
        Schema::create('use_cases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->string('condition'); // novo | lavado | sujo | usado
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();

            // Preço: visibilidade configurável por produto (visível ou "sob consulta")
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('price_visible')->default(true);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->string('unit', 20)->default('un');

            // Especificações técnicas (usadas nos filtros e no assistente de medidas)
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('depth_cm')->nullable();
            $table->unsignedInteger('height_cm')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable(); // peso do item p/ frete
            $table->boolean('has_discharge_valve')->default(false);
            $table->boolean('has_liner')->default(false);
            $table->unsignedTinyInteger('loops_count')->nullable(); // qtde de alças
            $table->string('top_type')->nullable();    // aberto | válvula | saia
            $table->string('bottom_type')->nullable(); // fechado | válvula

            $table->string('video_url')->nullable(); // YouTube/arquivo
            $table->unsignedInteger('stock_quantity')->nullable(); // null = sob consulta
            $table->string('availability')->default('disponivel'); // disponivel | sob_consulta | esgotado

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
            $table->index('condition');
            $table->index('capacity_kg');
        });

        // Variações (medidas/capacidades diferentes do mesmo produto)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // ex: "90 x 90 x 120 cm — 1.000 kg"
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('depth_cm')->nullable();
            $table->unsignedInteger('height_cm')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Fotos dos produtos
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Preço por faixa de quantidade (B2B: "a partir de 10 un: R$ X")
        Schema::create('quantity_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('min_qty');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        // Atributos dinâmicos (admin cria novos filtros sem programador)
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('select'); // select | boolean | number
            $table->string('unit', 20)->nullable();
            $table->boolean('is_filterable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
            $table->unique(['product_id', 'attribute_id']);
        });

        // Pivot produto ↔ uso recomendado
        Schema::create('product_use_case', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('use_case_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'use_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_use_case');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('quantity_prices');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('use_cases');
        Schema::dropIfExists('categories');
    }
};
