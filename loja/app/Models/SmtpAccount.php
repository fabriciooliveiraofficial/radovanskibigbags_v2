<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmtpAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    public function mailerConfig(): array
    {
        return [
            'transport'  => 'smtp',
            'host'       => $this->host,
            'port'       => $this->port,
            'encryption' => $this->encryption,
            'username'   => $this->username,
            'password'   => $this->password,
            'timeout'    => 30,
        ];
    }
}
