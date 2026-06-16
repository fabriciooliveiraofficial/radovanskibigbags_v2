<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('type')->default('orcamento')->after('id');           // orcamento | pedido
            $table->foreignId('quote_request_id')->nullable()->after('customer_id')
                ->constrained('quote_requests')->nullOnDelete();
            $table->string('shipping_cep', 9)->nullable()->after('shipping_cost');
            $table->decimal('total_weight_kg', 10, 3)->nullable()->after('shipping_cep');
            $table->unsignedSmallInteger('delivery_days')->nullable()->after('total_weight_kg');

            $table->index('type');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropConstrainedForeignId('quote_request_id');
            $table->dropColumn(['type', 'shipping_cep', 'total_weight_kg', 'delivery_days']);
        });
    }
};
