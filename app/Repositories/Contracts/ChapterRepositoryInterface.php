<?php

namespace App\Repositories\Contracts;

use App\Models\Chapter;
use Illuminate\Database\Eloquent\Collection;

interface ChapterRepositoryInterface
{
    /**
     * Get all chapters with optional language filter.
     */
    public function all(?string $languageCode = 'en'): Collection;

    /**
     * Find chapter by ID.
     */
    public function findById(int $id): ?Chapter;

    /**
     * Find chapter by chapter_id (1-114).
     */
    public function findByChapterId(int $chapterId, ?string $languageCode = 'en'): ?Chapter;

    /**
     * Get chapter with translations and info.
     */
    public function findWithDetails(int $chapterId, string $languageCode = 'en', ?string $locale = null): ?Chapter;

    /**
     * Search chapters by term.
     */
    public function search(string $term, ?string $languageCode = 'en'): Collection;

    /**
     * Get chapters by revelation place.
     */
    public function getByRevelationPlace(string $place, ?string $languageCode = 'en'): Collection;

    /**
     * Get chapters ordered by revelation order.
     */
    public function getByRevelationOrder(?string $languageCode = 'en'): Collection;

    /**
     * Create or update chapter.
     */
    public function createOrUpdate(array $data): Chapter;

    /**
     * Sync chapter with translated name.
     */
    public function syncTranslation(int $chapterId, string $languageCode, array $translationData): void;

    /**
     * Sync chapter info.
     */
    public function syncInfo(int $chapterId, string $languageCode, array $infoData, ?string $locale = null): void;

    /**
     * Delete chapter by ID.
     */
    public function delete(int $id): bool;

    /**
     * Check if chapter exists by chapter_id.
     */
    public function exists(int $chapterId): bool;

    /**
     * Get total chapters count.
     */
    public function count(): int;

    /**
     * Paginate chapters.
     */
    public function paginate(int $perPage = 15, ?string $languageCode = 'en');
}
