<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'revelation_place',
        'revelation_order',
        'bismillah_pre',
        'name_simple',
        'name_complex',
        'name_arabic',
        'verses_count',
        'pages',
    ];

    protected $casts = [
        'chapter_id' => 'integer',
        'revelation_order' => 'integer',
        'bismillah_pre' => 'boolean',
        'verses_count' => 'integer',
        'pages' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the translated names for the chapter.
     */
    public function translatedNames(): HasMany
    {
        return $this->hasMany(ChapterTranslatedName::class);
    }

    /**
     * Get the chapter information in different languages.
     */
    public function infos(): HasMany
    {
        return $this->hasMany(ChapterInfo::class);
    }

    /**
     * Get translated name for a specific language.
     */
    public function getTranslatedName(string $languageCode = 'en'): ?ChapterTranslatedName
    {
        return $this->translatedNames()
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Get chapter info for a specific language.
     */
    public function getInfo(string $languageCode = 'en', ?string $locale = null): ?ChapterInfo
    {
        $query = $this->infos()->where('language_code', $languageCode);

        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->first();
    }

    /**
     * Scope: Search chapters by name (simple, complex, or arabic).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name_simple', 'ILIKE', "%{$term}%")
                ->orWhere('name_complex', 'ILIKE', "%{$term}%")
                ->orWhere('name_arabic', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by revelation place.
     */
    public function scopeRevelationPlace(Builder $query, string $place): Builder
    {
        return $query->where('revelation_place', $place);
    }

    /**
     * Scope: Filter by chapter ID.
     */
    public function scopeByChapterId(Builder $query, int $chapterId): Builder
    {
        return $query->where('chapter_id', $chapterId);
    }

    /**
     * Scope: Order by revelation order.
     */
    public function scopeOrderByRevelation(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('revelation_order', $direction);
    }

    /**
     * Scope: With translations for specific language.
     */
    public function scopeWithTranslation(Builder $query, string $languageCode = 'en'): Builder
    {
        return $query->with(['translatedNames' => function ($q) use ($languageCode) {
            $q->where('language_code', $languageCode);
        }]);
    }

    /**
     * Scope: With info for specific language.
     */
    public function scopeWithInfo(Builder $query, string $languageCode = 'en', ?string $locale = null): Builder
    {
        return $query->with(['infos' => function ($q) use ($languageCode, $locale) {
            $q->where('language_code', $languageCode);
            if ($locale) {
                $q->where('locale', $locale);
            }
        }]);
    }

    /**
     * Get page range as a readable string.
     */
    public function getPageRangeAttribute(): string
    {
        $pages = $this->pages;
        if (empty($pages) || count($pages) < 2) {
            return 'N/A';
        }

        return $pages[0] === $pages[1]
            ? "Page {$pages[0]}"
            : "Pages {$pages[0]}-{$pages[1]}";
    }

    /**
     * Get start page number.
     */
    public function getStartPageAttribute(): ?int
    {
        return $this->pages[0] ?? null;
    }

    /**
     * Get end page number.
     */
    public function getEndPageAttribute(): ?int
    {
        return $this->pages[1] ?? null;
    }

    /**
     * Check if chapter is Makki.
     */
    public function isMakki(): bool
    {
        return $this->revelation_place === 'makkah';
    }

    /**
     * Check if chapter is Madani.
     */
    public function isMadani(): bool
    {
        return $this->revelation_place === 'madinah';
    }

    /**
     * Get chapter with all translations.
     */
    public function toArrayWithTranslations(string $languageCode = 'en'): array
    {
        $data = $this->toArray();
        $translation = $this->getTranslatedName($languageCode);

        if ($translation) {
            $data['translated_name'] = [
                'language_name' => $translation->language_name,
                'name' => $translation->name,
            ];
        }

        return $data;
    }
}
