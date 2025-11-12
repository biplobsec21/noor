<?php

namespace App\DTOs\QuranApi;

class Language
{
    public function __construct(
        public int $id,
        public string $name,
        public string $iso_code,
        public string $native_name,
        public string $direction,
        public int $translations_count,
        public ?TranslatedName $translated_name = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            iso_code: $data['iso_code'],
            native_name: $data['native_name'],
            direction: $data['direction'],
            translations_count: $data['translations_count'],
            translated_name: isset($data['translated_name'])
                ? TranslatedName::fromArray($data['translated_name'])
                : null
        );
    }
}
