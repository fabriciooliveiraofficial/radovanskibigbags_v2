<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('smtp_account_id')->nullable()->constrained()->nullOnDelete();
            $table->json('to_recipients');
            $table->json('cc_recipients')->nullable();
            $table->string('subject');
            $table->string('status')->default('enviado');  // enviado | falhou | aberto
            $table->text('error')->nullable();
            $table->string('open_token', 40)->nullable()->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->index('quote_id');
            $table->index('open_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
