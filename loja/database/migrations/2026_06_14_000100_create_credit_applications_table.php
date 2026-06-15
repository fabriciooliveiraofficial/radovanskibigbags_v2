<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ficha cadastral B2B: dados da empresa para liberar pagamento via boleto
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');             // razão social
            $table->string('trade_name')->nullable();   // nome fantasia
            $table->string('document', 20);             // CNPJ
            $table->string('state_registration', 20)->nullable(); // inscrição estadual
            $table->string('contact_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->text('notes')->nullable(); // volume esperado, referências, etc.
            $table->string('status')->default('pendente'); // pendente | aprovado | reprovado
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('document');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};
