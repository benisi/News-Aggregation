<?php

namespace App\Actions\Articles;

use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchAuthorsAction
{
    public function execute(?string $searchTerm, int $perPage): AnonymousResourceCollection
    {
        $query = Author::query()->with('source');

        if ($searchTerm) {
            $lowerSearchTerm = strtolower($searchTerm);

            $query->where(function ($q) use ($lowerSearchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchTerm}%"])
                    ->orWhereHas('source', function ($sourceQuery) use ($lowerSearchTerm) {
                        $sourceQuery->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchTerm}%"]);
                    });
            });
        }

        $authors = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return AuthorResource::collection($authors);
    }
}
