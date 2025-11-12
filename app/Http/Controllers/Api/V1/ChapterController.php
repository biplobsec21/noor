<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ChapterRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChapterController extends Controller
{
    public function __construct(
        private ChapterRepositoryInterface $chapterRepository
    ) {}

    /**
     * Get all chapters.
     * Automatically syncs from API if database is empty.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $languageCode = $request->get('language', 'en');
        $perPage = $request->get('per_page', 15);

        try {
            \Log::info("Chapter index called", ['language' => $languageCode]);

            $chapters = $this->chapterRepository->all($languageCode);

            // Map chapters with fallback for missing translations
            $chaptersData = $chapters->map(function ($chapter) use ($languageCode) {
                $data = $chapter->toArray();

                $translation = $chapter->getTranslatedName($languageCode);
                if ($translation) {
                    $data['translated_name'] = [
                        'language_name' => $translation->language_name,
                        'name' => $translation->name,
                    ];
                } else {
                    // Fallback to English if translation doesn't exist
                    $englishTranslation = $chapter->getTranslatedName('en');
                    $data['translated_name'] = $englishTranslation ? [
                        'language_name' => $englishTranslation->language_name . ' (fallback)',
                        'name' => $englishTranslation->name,
                    ] : null;
                }

                return $data;
            });

            return response()->json([
                'data' => $chaptersData,
                'meta' => [
                    'count' => $chapters->count(),
                    'language' => $languageCode,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Chapter index error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Unable to fetch chapters',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single chapter by chapter_id.
     * Automatically syncs from API if not found in database.
     *
     * @param Request $request
     * @param int $chapterId
     * @return JsonResponse
     */
    public function show(Request $request, int $chapterId): JsonResponse
    {
        if ($chapterId < 1 || $chapterId > 114) {
            return response()->json([
                'error' => 'Invalid chapter ID. Must be between 1 and 114.',
            ], 400);
        }

        $languageCode = $request->get('language', 'en');

        try {
            // This will auto-sync from API if not found in database
            $chapter = $this->chapterRepository->findByChapterId($chapterId, $languageCode);

            if (!$chapter) {
                return response()->json([
                    'error' => 'Chapter not found',
                ], 404);
            }

            return response()->json([
                'data' => $chapter->toArrayWithTranslations($languageCode),
                'meta' => [
                    'language' => $languageCode,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chapter info/details.
     * Automatically syncs from API if not found in database.
     *
     * @param Request $request
     * @param int $chapterId
     * @return JsonResponse
     */
    public function info(Request $request, int $chapterId): JsonResponse
    {
        if ($chapterId < 1 || $chapterId > 114) {
            return response()->json([
                'error' => 'Invalid chapter ID. Must be between 1 and 114.',
            ], 400);
        }

        $languageCode = $request->get('language', 'en');
        $locale = $request->get('locale');

        try {
            // This will auto-sync from API if not found in database
            $chapter = $this->chapterRepository->findWithDetails($chapterId, $languageCode, $locale);

            if (!$chapter) {
                return response()->json([
                    'error' => 'Chapter not found',
                ], 404);
            }

            $info = $chapter->infos->first();

            if (!$info) {
                return response()->json([
                    'error' => 'Chapter info not found for the requested language',
                ], 404);
            }

            return response()->json([
                'data' => [
                    'id' => $info->id,
                    'chapter_id' => $chapter->chapter_id,
                    'text' => $info->text,
                    'short_text' => $info->short_text,
                    'source' => $info->source,
                    'language_name' => $info->language_name,
                ],
                'meta' => [
                    'language' => $languageCode,
                    'locale' => $locale,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapter info',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search chapters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'language' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $searchTerm = $request->get('q');
        $languageCode = $request->get('language', 'en');

        try {
            $chapters = $this->chapterRepository->search($searchTerm, $languageCode);

            return response()->json([
                'data' => $chapters->map(fn($chapter) => $chapter->toArrayWithTranslations($languageCode)),
                'meta' => [
                    'search_term' => $searchTerm,
                    'language' => $languageCode,
                    'count' => $chapters->count(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chapters by revelation place.
     *
     * @param Request $request
     * @param string $place
     * @return JsonResponse
     */
    public function byRevelationPlace(Request $request, string $place): JsonResponse
    {
        if (!in_array($place, ['makkah', 'madinah'])) {
            return response()->json([
                'error' => 'Invalid revelation place. Must be "makkah" or "madinah".',
            ], 400);
        }

        $languageCode = $request->get('language', 'en');

        try {
            $chapters = $this->chapterRepository->getByRevelationPlace($place, $languageCode);

            return response()->json([
                'data' => $chapters->map(fn($chapter) => $chapter->toArrayWithTranslations($languageCode)),
                'meta' => [
                    'revelation_place' => $place,
                    'language' => $languageCode,
                    'count' => $chapters->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapters',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chapters ordered by revelation order.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byRevelationOrder(Request $request): JsonResponse
    {
        $languageCode = $request->get('language', 'en');

        try {
            $chapters = $this->chapterRepository->getByRevelationOrder($languageCode);

            return response()->json([
                'data' => $chapters->map(fn($chapter) => $chapter->toArrayWithTranslations($languageCode)),
                'meta' => [
                    'order_by' => 'revelation_order',
                    'language' => $languageCode,
                    'count' => $chapters->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapters',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force refresh chapter from API.
     *
     * @param Request $request
     * @param int $chapterId
     * @return JsonResponse
     */
    public function refresh(Request $request, int $chapterId): JsonResponse
    {
        if ($chapterId < 1 || $chapterId > 114) {
            return response()->json([
                'error' => 'Invalid chapter ID. Must be between 1 and 114.',
            ], 400);
        }

        $languageCode = $request->get('language', 'en');

        try {
            $chapter = $this->chapterRepository->refreshFromApi($chapterId, $languageCode);

            if (!$chapter) {
                return response()->json([
                    'error' => 'Failed to refresh chapter from API',
                ], 500);
            }

            return response()->json([
                'message' => 'Chapter refreshed successfully',
                'data' => $chapter->toArrayWithTranslations($languageCode),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to refresh chapter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all chapters from API (admin endpoint).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncAll(Request $request): JsonResponse
    {
        $languageCode = $request->get('language', 'en');

        try {
            $this->chapterRepository->syncAllChaptersFromApi($languageCode);

            $count = $this->chapterRepository->count();

            return response()->json([
                'message' => 'All chapters synced successfully',
                'meta' => [
                    'total_chapters' => $count,
                    'language' => $languageCode,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync chapters',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
