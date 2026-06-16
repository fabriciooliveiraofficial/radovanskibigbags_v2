<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteRequest extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'novo' => 'Novo',
        'atendido' => 'Atendido',
    ];

    public const BOLETO_STATUSES = [
        'aguardando_aprovacao' => 'Aguardando aprovação',
        'aprovado' => 'Aprovado',
        'rejeitado' => 'Rejeitado',
        'emitido' => 'Boleto emitido',
        'pago' => 'Pago',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'due_date' => 'date',
        ];
    }

    public function creditApplication(): BelongsTo
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }
}
