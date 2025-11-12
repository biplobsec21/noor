<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository
    ) {}

    /**
     * Get all languages.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $languages = $this->languageRepository->all();

            return response()->json([
                'data' => $languages,
                'meta' => [
                    'count' => $languages->count(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch languages',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get language by ISO code.
     */
    public function show(Request $request, string $isoCode): JsonResponse
    {
        try {
            $language = $this->languageRepository->findByIsoCode($isoCode);

            if (!$language) {
                return response()->json([
                    'error' => 'Language not found',
                ], 404);
            }

            return response()->json([
                'data' => $language,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch language',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search languages.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $searchTerm = $request->get('q');

        try {
            $languages = $this->languageRepository->search($searchTerm);

            return response()->json([
                'data' => $languages,
                'meta' => [
                    'search_term' => $searchTerm,
                    'count' => $languages->count(),
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
     * Get languages by direction.
     */
    public function byDirection(Request $request, string $direction): JsonResponse
    {
        if (!in_array($direction, ['ltr', 'rtl'])) {
            return response()->json([
                'error' => 'Invalid direction. Must be "ltr" or "rtl".',
            ], 400);
        }

        try {
            $languages = $this->languageRepository->getByDirection($direction);

            return response()->json([
                'data' => $languages,
                'meta' => [
                    'direction' => $direction,
                    'count' => $languages->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch languages',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync languages from API (admin endpoint).
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $this->languageRepository->syncAllFromApi();

            $count = $this->languageRepository->count();

            return response()->json([
                'message' => 'Languages synced successfully',
                'meta' => [
                    'total_languages' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync languages',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
