<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Link extends Model
{
    protected $fillable = [
        'user_id',
        'original_url',
        'code',
    ];

    protected static function booted(): void
    {
        static::creating(function (Link $link): void {
            if (! $link->code) {
                $link->code = self::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        do {
            $code = Str::random(6);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    protected function shortUrl(): Attribute
    {
        return Attribute::get(fn () => url($this->code));
    }
}
