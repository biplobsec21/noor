<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChapterInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chapter_info';

    protected $fillable = [
        'chapter_id',
        'language_code',
        'language_name',
        'locale',
        'text',
        'short_text',
        'source',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the chapter that owns the info.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Scope: Filter by language code.
     */
    public function scopeByLanguage($query, string $languageCode)
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Scope: Filter by locale.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope: Search in text and short text.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('text', 'ILIKE', "%{$term}%")
                ->orWhere('short_text', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Get excerpt from short text.
     */
    public function getExcerptAttribute(int $length = 150): string
    {
        if (strlen($this->short_text) <= $length) {
            return $this->short_text;
        }

        return substr($this->short_text, 0, $length) . '...';
    }

    /**
     * Check if info has locale specified.
     */
    public function hasLocale(): bool
    {
        return !empty($this->locale);
    }
}
