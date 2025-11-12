<?php

namespace App\DTOs\QuranApi;

class TranslatedName
{
    public function __construct(
        public string $language_name,
        public string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['language_name'],
            $data['name']
        );
    }
}
