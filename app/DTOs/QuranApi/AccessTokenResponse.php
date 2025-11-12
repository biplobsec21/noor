<?php

namespace App\DTOs\QuranApi;

class AccessTokenResponse
{
    public function __construct(
        public string $access_token,
        public int $expires_in,
        public string $scope,
        public string $token_type
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            access_token: $data['access_token'],
            expires_in: $data['expires_in'],
            scope: $data['scope'],
            token_type: $data['token_type']
        );
    }
}
