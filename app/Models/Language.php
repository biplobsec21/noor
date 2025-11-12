<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'language_id',
        'name',
        'iso_code',
        'native_name',
        'direction',
        'translations_count',
        'translated_name',
        'translated_language_name',
    ];

    protected $casts = [
        'language_id' => 'integer',
        'translations_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Scope: Filter by ISO code.
     */
    public function scopeByIsoCode($query, string $isoCode)
    {
        return $query->where('iso_code', $isoCode);
    }

    /**
     * Scope: Filter by direction (ltr/rtl).
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: Search in name or native name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
                ->orWhere('native_name', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Check if language is RTL.
     */
    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    /**
     * Check if language is LTR.
     */
    public function isLtr(): bool
    {
        return $this->direction === 'ltr';
    }
    /**
     * Get the full translated name object.
     */
    public function getTranslatedNameAttribute(): array
    {
        return [
            'name' => $this->attributes['translated_name'],
            'language_name' => $this->attributes['translated_language_name'],
        ];
    }
}
