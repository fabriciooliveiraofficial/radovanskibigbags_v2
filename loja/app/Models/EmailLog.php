<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'to_recipients'  => 'array',
            'cc_recipients'  => 'array',
            'sent_at'        => 'datetime',
            'opened_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailLog $log) {
            $log->open_token ??= Str::random(40);
            $log->sent_at    ??= now();
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function smtpAccount(): BelongsTo
    {
        return $this->belongsTo(SmtpAccount::class);
    }

    public function trackingPixelUrl(): string
    {
        return route('email.pixel', $this->open_token);
    }

    public function wasOpened(): bool
    {
        return $this->opened_at !== null;
    }
}
