<?php

namespace App\Actions\Articles;

use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchAuthorsAction
{
    public function execute(?string $searchTerm): AnonymousResourceCollection
    {
        $query = Author::query()->with('source');

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('source', function ($sourceQuery) use ($searchTerm) {
                        $sourceQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $authors = $query->orderBy('name')->limit(Author::FETCH_DATA_LIMIT)->get();

        return AuthorResource::collection($authors);
    }
}
