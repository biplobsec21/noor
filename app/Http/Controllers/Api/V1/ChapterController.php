<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QuranApi\ChapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function __construct(private ChapterService $chapterService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $language = $request->get('language', config('quran.default_language'));
            $forceRefresh = $request->boolean('refresh', false);

            $chaptersResponse = $this->chapterService->getChapters($language, $forceRefresh);

            return response()->json([
                'data' => $chaptersResponse->chapters,
                'meta' => [
                    'count' => $chaptersResponse->chapters->count(),
                    'language' => $language,
                    'timestamp' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapters',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    public function show(Request $request, $id) // Add $id parameter here
    {
        // dd([
        //     'id' => $id,
        //     'request_all' => $request->all(),
        //     'route_parameters' => $request->route()->parameters(),
        //     'query_parameters' => $request->query()
        // ]);

        try {
            // Validate chapter ID
            if (!$this->chapterService->validateChapterId((int) $id)) {
                return response()->json([
                    'error' => 'Invalid chapter ID. Must be between 1 and 114.',
                ], 400);
            }

            $language = $request->get('language', config('quran.default_language'));

            $chapter = $this->chapterService->getChapter((int) $id, $language);

            if (!$chapter) {
                return response()->json([
                    'error' => 'Chapter not found',
                ], 404);
            }

            return response()->json([
                'data' => $chapter,
                'meta' => [
                    'language' => $language,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapter',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    public function info(Request $request, int $id): JsonResponse
    {
        try {
            // Validate chapter ID
            if (!$this->chapterService->validateChapterId($id)) {
                return response()->json([
                    'error' => 'Invalid chapter ID. Must be between 1 and 114.',
                ], 400);
            }

            $language = $request->get('language', config('quran.default_language'));
            $locale = $request->get('locale');

            $chapterInfo = $this->chapterService->getChapterInfo($id, $language, $locale);

            if (!$chapterInfo) {
                return response()->json([
                    'error' => 'Chapter info not found',
                ], 404);
            }

            return response()->json([
                'data' => $chapterInfo,
                'meta' => [
                    'language' => $language,
                    'locale' => $locale,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch chapter info',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->get('q', '');
            $language = $request->get('language', config('quran.default_language'));

            if (empty($searchTerm)) {
                return response()->json([
                    'error' => 'Search term is required',
                ], 400);
            }

            $chapters = $this->chapterService->searchChapters($searchTerm, $language);

            return response()->json([
                'data' => $chapters,
                'meta' => [
                    'search_term' => $searchTerm,
                    'language' => $language,
                    'count' => $chapters->count(),
                    'timestamp' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to search chapters',
                'message' => $e->getMessage(),
            ], 503);
        }
    }
    public function debug(int $id)
    {
        try {
            $language = 'en';
            $url = env('QURAN_API_BASE_URL') . '/content/api/v4/chapters/' . $id;

            $accessToken = app(\App\Services\QuranApi\AuthService::class)->getAccessToken();

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-auth-token' => $accessToken,
                'x-client-id' => env('QURAN_CLIENT_ID'),
                'Accept' => 'application/json',
            ])->get($url, ['language' => $language]);

            return response()->json([
                'status' => $response->status(),
                'raw_response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
