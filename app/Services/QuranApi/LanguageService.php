<?php

namespace App\Services\QuranApi;

use App\DTOs\QuranApi\LanguagesResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LanguageService
{
    private AuthService $authService;
    private string $baseUrl;
    private string $clientId;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->baseUrl = config('quran.api.base_url') ?: env('QURAN_API_BASE_URL');
        $this->clientId = config('quran.credentials.client_id') ?: env('QURAN_CLIENT_ID');
    }

    public function getLanguages(bool $forceRefresh = false): LanguagesResponse
    {
        $cacheKey = "quran_languages";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $languagesData = Cache::remember($cacheKey, config('quran.cache.languages_ttl', 86400), function () {
            return $this->makeApiRequest('resources/languages');
        });

        return LanguagesResponse::fromArray($languagesData);
    }

    public function getLanguageByIsoCode(string $isoCode): ?\App\DTOs\QuranApi\Language
    {
        $languages = $this->getLanguages();

        foreach ($languages->languages as $language) {
            if ($language->iso_code === $isoCode) {
                return $language;
            }
        }

        return null;
    }

    private function makeApiRequest(string $endpoint, array $queryParams = []): ?array
    {
        $accessToken = $this->authService->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Unable to obtain access token');
        }

        $baseUrl = rtrim($this->baseUrl, '/');
        $url = $baseUrl . '/content/api/v4/' . ltrim($endpoint, '/');

        Log::info('Making Quran API request for languages', [
            'endpoint' => $endpoint,
            'url' => $url
        ]);

        $response = Http::withHeaders([
            'x-auth-token' => $accessToken,
            'x-client-id' => $this->clientId,
            'Accept' => 'application/json',
        ])->timeout(30)->get($url, $queryParams);

        if ($response->successful()) {
            return $response->json();
        }

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

        Log::error('Quran API Languages Request Failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        if ($response->status() === 404) {
            return null;
        }

        throw new \Exception("Quran API error ({$response->status()}): " . $response->body());
    }
}
