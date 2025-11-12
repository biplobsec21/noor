<?php

namespace App\Repositories;

use App\Models\Chapter;
use App\Models\ChapterTranslatedName;
use App\Models\ChapterInfo;
use App\Repositories\Contracts\ChapterRepositoryInterface;
use App\Services\QuranApi\ChapterService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChapterRepository implements ChapterRepositoryInterface
{
    public function __construct(
        private ChapterService $chapterService
    ) {}

    /**
     * Get all chapters with optional language filter.
     * If not in DB, fetch from API and store.
     */
    public function all(?string $languageCode = 'en'): Collection
    {
        \Log::info("ChapterRepository::all() called with language: {$languageCode}");

        // Check if we have any chapters at all
        $chapterCount = Chapter::count();

        // Check if we have translations for the requested language
        $translationCount = ChapterTranslatedName::where('language_code', $languageCode)->count();

        \Log::info("Chapters: {$chapterCount}, Translations for {$languageCode}: {$translationCount}");

        // If no chapters exist, sync everything
        if ($chapterCount === 0) {
            \Log::info("No chapters found, syncing all data for language: {$languageCode}");
            $this->syncAllChaptersFromApi($languageCode);
        }
        // If chapters exist but no translations for this language, sync only translations
        else if ($translationCount === 0) {
            \Log::info("Chapters exist but no translations for {$languageCode}, syncing translations only");
            $this->syncTranslationsOnly($languageCode);
        }
        // If we have chapters but missing some translations, sync missing ones
        else if ($translationCount < $chapterCount) {
            \Log::info("Missing some translations for {$languageCode}, syncing missing translations");
            $this->syncMissingTranslations($languageCode);
        } else {
            \Log::info("All data already exists for language: {$languageCode}");
        }

        $chapters = Chapter::withTranslation($languageCode)
            ->orderBy('chapter_id')
            ->get();

        \Log::info("Retrieved {$chapters->count()} chapters for language: {$languageCode}");

        return $chapters;
    }

    /**
     * Sync only translations for existing chapters
     */
    private function syncTranslationsOnly(string $languageCode): void
    {
        try {
            Log::info('Syncing translations only from API', ['language' => $languageCode]);

            $chaptersResponse = $this->chapterService->getChapters($languageCode);

            DB::transaction(function () use ($chaptersResponse, $languageCode) {
                foreach ($chaptersResponse->chapters as $chapterDto) {
                    // Find existing chapter
                    $chapter = Chapter::byChapterId($chapterDto->id)->first();

                    if ($chapter && $chapterDto->translated_name) {
                        // Sync only translation
                        $this->syncTranslation($chapterDto->id, $languageCode, [
                            'language_name' => $chapterDto->translated_name->language_name,
                            'name' => $chapterDto->translated_name->name,
                        ]);

                        Log::info("Synced translation for chapter {$chapterDto->id} in {$languageCode}");
                    }
                }
            });

            Log::info('Successfully synced translations from API');
        } catch (\Exception $e) {
            Log::error('Failed to sync translations from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync only missing translations
     */
    private function syncMissingTranslations(string $languageCode): void
    {
        try {
            Log::info('Syncing missing translations from API', ['language' => $languageCode]);

            $chaptersResponse = $this->chapterService->getChapters($languageCode);

            DB::transaction(function () use ($chaptersResponse, $languageCode) {
                foreach ($chaptersResponse->chapters as $chapterDto) {
                    // Check if translation already exists
                    $chapter = Chapter::byChapterId($chapterDto->id)->first();

                    if ($chapter && $chapterDto->translated_name) {
                        $existingTranslation = $chapter->getTranslatedName($languageCode);

                        if (!$existingTranslation) {
                            // Sync missing translation
                            $this->syncTranslation($chapterDto->id, $languageCode, [
                                'language_name' => $chapterDto->translated_name->language_name,
                                'name' => $chapterDto->translated_name->name,
                            ]);

                            Log::info("Synced missing translation for chapter {$chapterDto->id} in {$languageCode}");
                        }
                    }
                }
            });

            Log::info('Successfully synced missing translations from API');
        } catch (\Exception $e) {
            Log::error('Failed to sync missing translations from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Find chapter by database ID.
     */
    public function findById(int $id): ?Chapter
    {
        return Chapter::find($id);
    }

    /**
     * Find chapter by chapter_id (1-114).
     * If not in DB, fetch from API and store.
     */
    public function findByChapterId(int $chapterId, ?string $languageCode = 'en'): ?Chapter
    {
        $chapter = Chapter::byChapterId($chapterId)
            ->withTranslation($languageCode)
            ->first();

        // If not found, try to fetch from API
        if (!$chapter) {
            $this->syncChapterFromApi($chapterId, $languageCode);
            $chapter = Chapter::byChapterId($chapterId)
                ->withTranslation($languageCode)
                ->first();
        }

        return $chapter;
    }

    /**
     * Get chapter with translations and info.
     * Fetches info from API if not in database.
     */
    public function findWithDetails(int $chapterId, string $languageCode = 'en', ?string $locale = null): ?Chapter
    {
        $chapter = Chapter::byChapterId($chapterId)
            ->withTranslation($languageCode)
            ->withInfo($languageCode, $locale)
            ->first();

        // If chapter exists but info doesn't, fetch from API
        if ($chapter && $chapter->infos->isEmpty()) {
            $this->syncChapterInfoFromApi($chapterId, $languageCode, $locale);

            // Reload with info
            $chapter = Chapter::byChapterId($chapterId)
                ->withTranslation($languageCode)
                ->withInfo($languageCode, $locale)
                ->first();
        }

        return $chapter;
    }

    /**
     * Search chapters by term.
     */
    public function search(string $term, ?string $languageCode = 'en'): Collection
    {
        return Chapter::search($term)
            ->withTranslation($languageCode)
            ->get();
    }

    /**
     * Get chapters by revelation place.
     */
    public function getByRevelationPlace(string $place, ?string $languageCode = 'en'): Collection
    {
        return Chapter::revelationPlace($place)
            ->withTranslation($languageCode)
            ->orderBy('chapter_id')
            ->get();
    }

    /**
     * Get chapters ordered by revelation order.
     */
    public function getByRevelationOrder(?string $languageCode = 'en'): Collection
    {
        return Chapter::withTranslation($languageCode)
            ->orderByRevelation()
            ->get();
    }

    /**
     * Create or update chapter.
     */
    public function createOrUpdate(array $data): Chapter
    {
        return Chapter::updateOrCreate(
            ['chapter_id' => $data['chapter_id']],
            [
                'revelation_place' => $data['revelation_place'],
                'revelation_order' => $data['revelation_order'],
                'bismillah_pre' => $data['bismillah_pre'] ?? false,
                'name_simple' => $data['name_simple'],
                'name_complex' => $data['name_complex'],
                'name_arabic' => $data['name_arabic'],
                'verses_count' => $data['verses_count'],
                'pages' => $data['pages'],
            ]
        );
    }

    /**
     * Sync chapter with translated name.
     */
    public function syncTranslation(int $chapterId, string $languageCode, array $translationData): void
    {
        $chapter = Chapter::byChapterId($chapterId)->firstOrFail();

        ChapterTranslatedName::updateOrCreate(
            [
                'chapter_id' => $chapter->id,
                'language_code' => $languageCode,
            ],
            [
                'language_name' => $translationData['language_name'],
                'name' => $translationData['name'],
            ]
        );
    }

    /**
     * Sync chapter info.
     */
    public function syncInfo(int $chapterId, string $languageCode, array $infoData, ?string $locale = null): void
    {
        $chapter = Chapter::byChapterId($chapterId)->firstOrFail();

        ChapterInfo::updateOrCreate(
            [
                'chapter_id' => $chapter->id,
                'language_code' => $languageCode,
                'locale' => $locale,
            ],
            [
                'language_name' => $infoData['language_name'] ?? $languageCode,
                'text' => $infoData['text'],
                'short_text' => $infoData['short_text'],
                'source' => $infoData['source'],
            ]
        );
    }

    /**
     * Delete chapter by ID.
     */
    public function delete(int $id): bool
    {
        $chapter = $this->findById($id);

        if (!$chapter) {
            return false;
        }

        return $chapter->delete();
    }

    /**
     * Check if chapter exists by chapter_id.
     */
    public function exists(int $chapterId): bool
    {
        return Chapter::byChapterId($chapterId)->exists();
    }

    /**
     * Get total chapters count.
     */
    public function count(): int
    {
        return Chapter::count();
    }

    /**
     * Paginate chapters.
     */
    public function paginate(int $perPage = 15, ?string $languageCode = 'en')
    {
        return Chapter::withTranslation($languageCode)
            ->orderBy('chapter_id')
            ->paginate($perPage);
    }

    /**
     * Sync all chapters from Quran API to database.
     */
    public function syncAllChaptersFromApi(?string $languageCode = 'en'): void
    {
        try {
            Log::info('Starting to sync all chapters from API', ['language' => $languageCode]);

            $chaptersResponse = $this->chapterService->getChapters($languageCode);

            DB::transaction(function () use ($chaptersResponse, $languageCode) {
                foreach ($chaptersResponse->chapters as $chapterDto) {
                    // Create/update chapter (base data - same for all languages)
                    $chapter = $this->createOrUpdate([
                        'chapter_id' => $chapterDto->id,
                        'revelation_place' => $chapterDto->revelation_place,
                        'revelation_order' => $chapterDto->revelation_order,
                        'bismillah_pre' => $chapterDto->bismillah_pre,
                        'name_simple' => $chapterDto->name_simple,
                        'name_complex' => $chapterDto->name_complex,
                        'name_arabic' => $chapterDto->name_arabic,
                        'verses_count' => $chapterDto->verses_count,
                        'pages' => $chapterDto->pages,
                    ]);

                    // Sync translation for the requested language
                    if ($chapterDto->translated_name) {
                        $this->syncTranslation($chapterDto->id, $languageCode, [
                            'language_name' => $chapterDto->translated_name->language_name,
                            'name' => $chapterDto->translated_name->name,
                        ]);
                    }

                    Log::info("Synced chapter {$chapterDto->id} for language: {$languageCode}");
                }
            });

            Log::info('Successfully synced all chapters from API for language: ' . $languageCode);
        } catch (\Exception $e) {
            Log::error('Failed to sync chapters from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync single chapter from API.
     */
    public function syncChapterFromApi(int $chapterId, ?string $languageCode = 'en'): ?Chapter
    {
        try {
            if (!$this->chapterService->validateChapterId($chapterId)) {
                throw new \InvalidArgumentException("Invalid chapter ID: {$chapterId}");
            }

            Log::info("Syncing chapter {$chapterId} from API", ['language' => $languageCode]);

            $chapterDto = $this->chapterService->getChapter($chapterId, $languageCode);

            if (!$chapterDto) {
                Log::warning("Chapter {$chapterId} not found in API");
                return null;
            }

            return DB::transaction(function () use ($chapterDto, $languageCode) {
                // Create/update chapter
                $chapter = $this->createOrUpdate([
                    'chapter_id' => $chapterDto->id,
                    'revelation_place' => $chapterDto->revelation_place,
                    'revelation_order' => $chapterDto->revelation_order,
                    'bismillah_pre' => $chapterDto->bismillah_pre,
                    'name_simple' => $chapterDto->name_simple,
                    'name_complex' => $chapterDto->name_complex,
                    'name_arabic' => $chapterDto->name_arabic,
                    'verses_count' => $chapterDto->verses_count,
                    'pages' => $chapterDto->pages,
                ]);

                // Sync translation
                if ($chapterDto->translated_name) {
                    $this->syncTranslation($chapterDto->id, $languageCode, [
                        'language_name' => $chapterDto->translated_name->language_name,
                        'name' => $chapterDto->translated_name->name,
                    ]);
                }

                Log::info("Successfully synced chapter {$chapterDto->id}");

                return $chapter;
            });
        } catch (\Exception $e) {
            Log::error("Failed to sync chapter {$chapterId} from API", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync chapter info from API.
     */
    public function syncChapterInfoFromApi(int $chapterId, ?string $languageCode = 'en', ?string $locale = null): void
    {
        try {
            Log::info("Syncing chapter info for chapter {$chapterId} from API", [
                'language' => $languageCode,
                'locale' => $locale
            ]);

            $chapterInfoDto = $this->chapterService->getChapterInfo($chapterId, $languageCode, $locale);

            if (!$chapterInfoDto) {
                Log::warning("Chapter info for chapter {$chapterId} not found in API");
                return;
            }

            $this->syncInfo($chapterId, $languageCode, [
                'language_name' => $chapterInfoDto->language_name,
                'text' => $chapterInfoDto->text,
                'short_text' => $chapterInfoDto->short_text,
                'source' => $chapterInfoDto->source,
            ], $locale);

            Log::info("Successfully synced chapter info for chapter {$chapterId}");
        } catch (\Exception $e) {
            Log::error("Failed to sync chapter info for chapter {$chapterId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Refresh chapter data from API (force update).
     */
    public function refreshFromApi(int $chapterId, ?string $languageCode = 'en'): ?Chapter
    {
        return $this->syncChapterFromApi($chapterId, $languageCode);
    }

    /**
     * Bulk sync chapters with progress tracking.
     */
    public function bulkSyncFromApi(array $chapterIds, ?string $languageCode = 'en'): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($chapterIds as $chapterId) {
            try {
                $chapter = $this->syncChapterFromApi($chapterId, $languageCode);

                if ($chapter) {
                    $results['success'][] = $chapterId;
                } else {
                    $results['failed'][] = [
                        'chapter_id' => $chapterId,
                        'reason' => 'Not found in API'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'chapter_id' => $chapterId,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get chapters with specific verse count range.
     */
    public function getByVerseCountRange(int $min, int $max, ?string $languageCode = 'en'): Collection
    {
        return Chapter::whereBetween('verses_count', [$min, $max])
            ->withTranslation($languageCode)
            ->orderBy('chapter_id')
            ->get();
    }

    /**
     * Get the shortest chapter.
     */
    public function getShortest(?string $languageCode = 'en'): ?Chapter
    {
        return Chapter::withTranslation($languageCode)
            ->orderBy('verses_count', 'asc')
            ->first();
    }

    /**
     * Get the longest chapter.
     */
    public function getLongest(?string $languageCode = 'en'): ?Chapter
    {
        return Chapter::withTranslation($languageCode)
            ->orderBy('verses_count', 'desc')
            ->first();
    }
}
