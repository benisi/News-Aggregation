<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class ArticleDTO
{
    public function __construct(
        public string $title,
        public ?string $content,
        public string $source,
        public ?string $category,
        public array $authors,
        public ?string $description,
        public string $published_at,
        public string $url,
        public ?string $image_url,
    ) {}

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content ?? '',
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => Carbon::parse($this->published_at)
        ];
    }
}
