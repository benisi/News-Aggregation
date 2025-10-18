<?php

namespace App\DTOs;

use Carbon\Carbon;

class ArticleDTO
{
    public function __construct(
        public string $title,
        public ?string $content,
        public string $source,
        public ?string $category,
        public ?string $author,
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
            'author' => $this->author,
            'description' => $this->description,
            'content' => $this->content ?? '',
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => Carbon::parse($this->published_at)
        ];
    }
}
