<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChapterCache extends Model
{
    use HasFactory;

    protected $table = 'chapter_cache';

    protected $fillable = [
        'cache_key',
        'data',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope: Get only expired cache entries.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Get only valid (non-expired) cache entries.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Find by cache key.
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('cache_key', $key);
    }

    /**
     * Check if cache entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if cache entry is valid.
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Get the cached data with fallback.
     */
    public function getData(array $default = []): array
    {
        return $this->data ?? $default;
    }

    /**
     * Set the cached data.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Clean up expired cache entries.
     */
    public static function cleanup(): int
    {
        return self::expired()->delete();
    }
}
