<?php

namespace App\Repositories;

use App\Models\Language;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Services\QuranApi\LanguageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LanguageRepository implements LanguageRepositoryInterface
{
    public function __construct(
        private LanguageService $languageService
    ) {}

    public function all(): Collection
    {
        $count = Language::count();

        if ($count === 0) {
            $this->syncAllFromApi();
        }

        return Language::orderBy('name')->get();
    }

    public function findById(int $id): ?Language
    {
        return Language::find($id);
    }

    public function findByLanguageId(int $languageId): ?Language
    {
        $language = Language::where('language_id', $languageId)->first();

        if (!$language) {
            $this->syncAllFromApi();
            $language = Language::where('language_id', $languageId)->first();
        }

        return $language;
    }

    public function findByIsoCode(string $isoCode): ?Language
    {
        return Language::byIsoCode($isoCode)->first();
    }

    public function search(string $term): Collection
    {
        return Language::search($term)->get();
    }

    public function getByDirection(string $direction): Collection
    {
        return Language::byDirection($direction)->get();
    }

    public function createOrUpdate(array $data): Language
    {
        return Language::updateOrCreate(
            ['language_id' => $data['language_id']],
            [
                'name' => $data['name'],
                'iso_code' => $data['iso_code'],
                'native_name' => $data['native_name'],
                'direction' => $data['direction'],
                'translations_count' => $data['translations_count'],
                'translated_name' => $data['translated_name'] ?? null,
                'translated_language_name' => $data['translated_language_name'] ?? null,
            ]
        );
    }

    public function delete(int $id): bool
    {
        $language = $this->findById($id);

        if (!$language) {
            return false;
        }

        return $language->delete();
    }

    public function exists(int $languageId): bool
    {
        return Language::where('language_id', $languageId)->exists();
    }

    public function count(): int
    {
        return Language::count();
    }

    public function syncAllFromApi(): void
    {
        try {
            Log::info('Starting to sync all languages from API');

            $languagesResponse = $this->languageService->getLanguages();

            DB::transaction(function () use ($languagesResponse) {
                foreach ($languagesResponse->languages as $languageDto) {
                    // Debug: Check what we're getting from the API
                    Log::info("Processing language", [
                        'id' => $languageDto->id,
                        'name' => $languageDto->name,
                        'has_translated_name' => !is_null($languageDto->translated_name),
                        'translated_name' => $languageDto->translated_name?->name,
                        'translated_language_name' => $languageDto->translated_name?->language_name,
                    ]);

                    $translatedName = $languageDto->translated_name?->name;
                    $translatedLanguageName = $languageDto->translated_name?->language_name;

                    // If translated_name is null, use the name as fallback
                    if (is_null($translatedName)) {
                        $translatedName = $languageDto->name;
                        $translatedLanguageName = 'english'; // Default fallback
                        Log::warning("No translated_name for language {$languageDto->name}, using fallback");
                    }

                    $this->createOrUpdate([
                        'language_id' => $languageDto->id,
                        'name' => $languageDto->name,
                        'iso_code' => $languageDto->iso_code,
                        'native_name' => $languageDto->native_name,
                        'direction' => $languageDto->direction,
                        'translations_count' => $languageDto->translations_count,
                        'translated_name' => $translatedName,
                        'translated_language_name' => $translatedLanguageName,
                    ]);

                    Log::info("Synced language: {$languageDto->name} ({$languageDto->iso_code})");
                }
            });

            Log::info('Successfully synced all languages from API');
        } catch (\Exception $e) {
            Log::error('Failed to sync languages from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
