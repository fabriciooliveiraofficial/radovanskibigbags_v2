<?php

use App\Models\QuoteItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reaplica os hooks do model (subtotal - desconto = total, e recálculo da quote)
        // em cada item já gravado, para corrigir totais zerados pelo conflito entre o
        // Repeater de itens e o antigo RelationManager duplicado.
        QuoteItem::query()->each(fn (QuoteItem $item) => $item->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recálculo não é reversível.
    }
};
