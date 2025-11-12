<?php

namespace App\DTOs\QuranApi;

class Chapter
{
    public function __construct(
        public int $id,
        public string $revelation_place,
        public int $revelation_order,
        public bool $bismillah_pre,
        public string $name_simple,
        public string $name_complex,
        public string $name_arabic,
        public int $verses_count,
        public array $pages,
        public TranslatedName $translated_name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            revelation_place: $data['revelation_place'],
            revelation_order: $data['revelation_order'],
            bismillah_pre: $data['bismillah_pre'],
            name_simple: $data['name_simple'],
            name_complex: $data['name_complex'],
            name_arabic: $data['name_arabic'],
            verses_count: $data['verses_count'],
            pages: $data['pages'],
            translated_name: TranslatedName::fromArray($data['translated_name'])
        );
    }
}
