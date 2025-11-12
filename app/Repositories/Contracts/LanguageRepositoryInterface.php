<?php

namespace App\Repositories\Contracts;

use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;

interface LanguageRepositoryInterface
{
    /**
     * Get all languages.
     */
    public function all(): Collection;

    /**
     * Find language by ID.
     */
    public function findById(int $id): ?Language;

    /**
     * Find language by language_id (API ID).
     */
    public function findByLanguageId(int $languageId): ?Language;

    /**
     * Find language by ISO code.
     */
    public function findByIsoCode(string $isoCode): ?Language;

    /**
     * Search languages by term.
     */
    public function search(string $term): Collection;

    /**
     * Get languages by direction.
     */
    public function getByDirection(string $direction): Collection;

    /**
     * Create or update language.
     */
    public function createOrUpdate(array $data): Language;

    /**
     * Delete language by ID.
     */
    public function delete(int $id): bool;

    /**
     * Check if language exists by language_id.
     */
    public function exists(int $languageId): bool;

    /**
     * Get total languages count.
     */
    public function count(): int;

    /**
     * Sync all languages from API.
     */
    public function syncAllFromApi(): void;
}
