<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('payment_method')->default('whatsapp'); // whatsapp | boleto
            $table->foreignId('credit_application_id')->nullable()->after('payment_method')
                ->constrained()->nullOnDelete();
            $table->string('boleto_status')->nullable(); // aguardando_aprovacao | aprovado | rejeitado | emitido | pago
            $table->unsignedSmallInteger('payment_term_days')->nullable(); // 30 | 45 | 60
            $table->date('due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_application_id');
            $table->dropColumn(['payment_method', 'boleto_status', 'payment_term_days', 'due_date']);
        });
    }
};
