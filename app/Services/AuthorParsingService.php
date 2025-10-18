<?php

namespace App\Services;

use Illuminate\Support\Str;

class AuthorParsingService
{
    /**
     * Parses a raw author string into an array of clean, individual author names.
     * Handles cases like "By John Doe and Jane Smith"
     *
     * @param string|null $rawAuthorString
     * @return string[]
     */
    public function parse(?string $rawAuthorString): array
    {
        if (empty($rawAuthorString)) {
            return [];
        }

        $cleanedString = Str::of($rawAuthorString)
            ->lower()
            ->remove('by')
            ->trim();

        $delimiters = ['and', ','];
        $authors = [];
        $parts = explode($delimiters[0], $cleanedString);

        foreach ($parts as $part) {
            $subParts = explode($delimiters[1], $part);
            $authors = array_merge($authors, $subParts);
        }

        $finalAuthors = collect($authors)
            ->map(function (string $name) {
                return Str::of($name)->trim()->title()->toString();
            })
            ->filter()
            ->unique()
            ->all();

        return $finalAuthors;
    }
}
