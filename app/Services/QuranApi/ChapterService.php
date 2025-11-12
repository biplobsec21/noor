<?php

namespace App\Services\QuranApi;

use App\DTOs\QuranApi\ChaptersResponse;
use App\DTOs\QuranApi\ChapterDetail;
use App\DTOs\QuranApi\ChapterInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapterService
{
    private AuthService $authService;
    private string $baseUrl;
    private string $clientId;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->baseUrl = config('quran.api.base_url') ?: env('QURAN_API_BASE_URL');
        $this->clientId = config('quran.credentials.client_id') ?: env('QURAN_CLIENT_ID');

        // Add logging to verify values are loaded
        Log::info('ChapterService initialized', [
            'base_url' => $this->baseUrl,
            'client_id' => substr($this->clientId, 0, 8) . '...'
        ]);
    }

    public function getChapters(?string $language = null, bool $forceRefresh = false): ChaptersResponse
    {
        $language = $language ?: config('quran.default_language');
        $cacheKey = "quran_chapters_{$language}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $chaptersData = Cache::remember($cacheKey, config('quran.cache.chapters_ttl'), function () use ($language) {
            return $this->makeApiRequest('chapters', ['language' => $language]);
        });

        // dd('chaptersData', $chaptersData);
        return ChaptersResponse::fromArray($chaptersData);
    }

    public function getChapter(int $chapterId, ?string $language = null): ?ChapterDetail
    {

        $language = $language ?: config('quran.default_language');
        $cacheKey = "quran_chapter_{$chapterId}_{$language}";

        $chapterData = Cache::remember($cacheKey, config('quran.cache.chapter_ttl'), function () use ($chapterId, $language) {
            return $this->makeApiRequest("chapters/{$chapterId}", ['language' => $language]);
        });
        // dd('response', $chapterData);
        return $chapterData ? ChapterDetail::fromArray($chapterData['chapter']) : null;
        // dd($data);
    }

    public function getChapterInfo(int $chapterId, ?string $language = null, ?string $locale = null): ?ChapterInfo
    {
        $language = $language ?: config('quran.default_language');
        $cacheKey = "quran_chapter_info_{$chapterId}_{$language}" . ($locale ? "_{$locale}" : '');

        $params = ['language' => $language];
        // dd($cacheKey);
        if ($locale) {
            $params['locale'] = $locale;
        }
        dd($this->makeApiRequest("chapters/{$chapterId}/info", $params));
        $infoData = Cache::remember($cacheKey, config('quran.cache.chapter_info_ttl'), function () use ($chapterId, $params) {
            return $this->makeApiRequest("chapters/{$chapterId}/info", $params);
        });
        dd($infoData);
        return $infoData ? ChapterInfo::fromArray($infoData['chapter_info']) : null;
    }

    private function makeApiRequest(string $endpoint, array $queryParams = []): ?array
    {
        $accessToken = $this->authService->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Unable to obtain access token');
        }

        // More robust URL construction
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = $baseUrl . '/content/api/v4/' . ltrim($endpoint, '/');

        Log::info('Making Quran API request', [
            'endpoint' => $endpoint,
            'base_url' => $this->baseUrl,
            'full_url' => $url,
            'query_params' => $queryParams
        ]);

        $response = Http::withHeaders([
            'x-auth-token' => $accessToken,
            'x-client-id' => $this->clientId,
            'Accept' => 'application/json',
        ])->timeout(30)->get($url, $queryParams);


        if ($response->successful()) {
            Log::info('Quran API request successful', ['endpoint' => $endpoint]);
            return $response->json();
        }

        // Handle token expiration
        if ($response->status() === 401) {
            Log::warning('Token might be expired, clearing cache and retrying');
            $this->authService->clearCachedToken();
            $accessToken = $this->authService->getAccessToken();

            $response = Http::withHeaders([
                'x-auth-token' => $accessToken,
                'x-client-id' => $this->clientId,
                'Accept' => 'application/json',
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }
        }

        Log::error('Quran API Request Failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $response->body(),
            'url' => $url,
            'headers_sent' => [
                'x-auth-token' => substr($accessToken, 0, 20) . '...',
                'x-client-id' => $this->clientId
            ]
        ]);

        // For 404 responses, return null instead of throwing exception
        if ($response->status() === 404) {
            return null;
        }

        throw new \Exception("Quran API error ({$response->status()}): " . $response->body());
    }

    public function searchChapters(string $searchTerm, ?string $language = null): \Illuminate\Support\Collection
    {
        $chapters = $this->getChapters($language);

        return $chapters->chapters->filter(function ($chapter) use ($searchTerm) {
            return stripos($chapter->name_simple, $searchTerm) !== false ||
                stripos($chapter->name_complex, $searchTerm) !== false ||
                stripos($chapter->name_arabic, $searchTerm) !== false ||
                stripos($chapter->translated_name->name, $searchTerm) !== false;
        });
    }

    public function validateChapterId(int $chapterId): bool
    {
        return $chapterId >= 1 && $chapterId <= 114;
    }
}
