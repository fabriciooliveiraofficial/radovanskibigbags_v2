<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QuoteAttachment extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (QuoteAttachment $att) {
            if ($att->path && Storage::disk('public')->exists($att->path)) {
                $att->original_filename ??= basename($att->path);
                $att->mime_type         ??= Storage::disk('public')->mimeType($att->path);
                $att->size_bytes        ??= Storage::disk('public')->size($att->path);
            }
        });

        static::deleted(function (QuoteAttachment $att) {
            if ($att->path) {
                Storage::disk('public')->delete($att->path);
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function sizeFormatted(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1_048_576, 1).' MB';
    }

    public function typeIcon(): string
    {
        $mime = (string) $this->mime_type;
        if (str_contains($mime, 'pdf'))   return '📄';
        if (str_contains($mime, 'image')) return '🖼️';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return '📊';
        if (str_contains($mime, 'word'))  return '📝';

        return '📎';
    }
}
