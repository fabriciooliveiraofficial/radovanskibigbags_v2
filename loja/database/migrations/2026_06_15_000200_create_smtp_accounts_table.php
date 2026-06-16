<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // "Hostinger — fabricio"
            $table->string('host')->default('smtp.hostinger.com');
            $table->unsignedSmallInteger('port')->default(465);
            $table->string('encryption', 10)->nullable()->default('ssl'); // ssl | tls | null
            $table->string('username');
            $table->text('password');                            // encrypted cast
            $table->string('from_name')->nullable();
            $table->string('from_address');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_accounts');
    }
};
