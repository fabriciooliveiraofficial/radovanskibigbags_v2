<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
