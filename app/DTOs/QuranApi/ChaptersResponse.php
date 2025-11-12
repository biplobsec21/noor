<?php

namespace App\DTOs\QuranApi;

use Illuminate\Support\Collection;

class ChaptersResponse
{
    /** @var Collection<Chapter> */
    public Collection $chapters;

    public function __construct(array $chapters)
    {
        $this->chapters = collect($chapters)->map(function ($chapterData) {
            return Chapter::fromArray($chapterData);
        });
    }

    public static function fromArray(array $data): self
    {
        return new self($data['chapters']);
    }
}
