<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = [];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest();
    }

    /** Telefone só com dígitos, com DDI 55, pronto para wa.me */
    public function whatsappNumber(): string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);

        return str_starts_with($digits, '55') ? $digits : '55'.$digits;
    }
}
