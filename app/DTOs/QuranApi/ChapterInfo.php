<?php

namespace App\DTOs\QuranApi;

class ChapterInfo
{
    public function __construct(
        public int $id,
        public int $chapter_id,
        public string $text,
        public string $source,
        public string $language_name,
        public string $short_text
    ) {}

    public static function fromArray(array $data): self
    {
        // dd($data);
        return new self(
            id: $data['id'],
            text: $data['text'],
            source: $data['source'],
            language_name: $data['language_name'],
            short_text: $data['short_text'],
            chapter_id: $data['chapter_id'],
        );
    }
}
