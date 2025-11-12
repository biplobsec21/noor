<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChapterTranslatedName extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chapter_translated_names';

    protected $fillable = [
        'chapter_id',
        'language_code',
        'language_name',
        'name',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the chapter that owns the translated name.
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
     * Scope: Search by translated name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'ILIKE', "%{$term}%");
    }

    /**
     * Get the display name with language info.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->language_name})";
    }
}
