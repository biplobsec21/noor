<?php

namespace App\DTOs\QuranApi;

class LanguagesResponse
{
    public function __construct(
        public array $languages
    ) {}

    public static function fromArray(array $data): self
    {
        $languages = array_map(function ($languageData) {
            return Language::fromArray($languageData);
        }, $data['languages']);

        return new self($languages);
    }
}
