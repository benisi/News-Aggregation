<?php

namespace App\Actions\Articles;

use App\DTOs\ArticleFilterDTO;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListArticlesAction
{
    public function execute(ArticleFilterDTO $filters): AnonymousResourceCollection
    {
        $query = Article::query()
            ->with(['authors', 'source', 'category'])
            ->latest('published_at');

        if ($filters->search) {
            $searchTerm = strtolower($filters->search);

            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereHas('authors', function ($authorQuery) use ($searchTerm) {
                        $authorQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                    });
            });
        }

        if (!empty($filters->sourceIds)) {
            $query->whereIn('source_id', $filters->sourceIds);
        }

        if (!empty($filters->categoryIds)) {
            $query->whereIn('category_id', $filters->categoryIds);
        }

        if (!empty($filters->authorIds)) {
            $query->whereHas('authors', function ($q) use ($filters) {
                $q->whereIn('authors.id', $filters->authorIds);
            });
        }

        if ($filters->dateFrom) {
            $query->where('published_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->where('published_at', '<=', $filters->dateTo);
        }

        $articles = $query->paginate($filters->perPage)->withQueryString();

        return ArticleResource::collection($articles);
    }
}
