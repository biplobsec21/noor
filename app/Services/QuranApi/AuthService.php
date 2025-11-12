<?php

namespace App\Services\QuranApi;

use App\DTOs\QuranApi\AccessTokenResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $oauthUrl;

    public function __construct()
    {
        $this->clientId = env('QURAN_CLIENT_ID', 'd1adbefe-798f-4bb1-82d6-e72185ffa70a');
        $this->clientSecret = env('QURAN_CLIENT_SECRET', 'QHbBk5bMWHDhN73dNI2RhHnWTh');
        $this->oauthUrl = env('QURAN_OAUTH_URL', 'https://oauth2.quran.foundation/oauth2/token');
    }

    public function getAccessToken(): ?string
    {
        return Cache::remember('quran_api_access_token', 3500, function () {
            $tokenData = $this->requestNewToken();
            return $tokenData->access_token ?? null;
        });
    }

    private function requestNewToken(): ?AccessTokenResponse
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()->timeout(30)->post($this->oauthUrl, [
                    'grant_type' => 'client_credentials',
                    'scope' => 'content',
                ]);

            if ($response->successful()) {
                return AccessTokenResponse::fromArray($response->json());
            }

            Log::error('Quran API Auth Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Quran API Auth Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function clearCachedToken(): void
    {
        Cache::forget('quran_api_access_token');
    }
}
