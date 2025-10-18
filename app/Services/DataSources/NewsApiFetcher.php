<?php

namespace App\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use App\Exceptions\MaximumArticleResultException;
use Illuminate\Support\Facades\Http;

class NewsApiFetcher implements DataFetcherInterface
{
    protected string $endpoint;
    protected string $apiKey;

    const MAXIMUM_RESULT_REACHED = "maximumResultsReached";
    const RATE_LIMITING = "rateLimited";
    const PER_PAGE = 100;

    public function __construct(protected array $sources) {}

    public function fetch(int $page): ArticleCollection
    {
        $trimmedSources = array_map('trim', $this->sources);
        $sourceString = implode(',', $trimmedSources);

        $response = Http::withHeader('X-Api-Key', config('services.newsapi.key'))
            ->get(config('services.newsapi.endpoint'), [
                'language' => config('services.newsapi.language'),
                'page' => $page,
                'pageSize' => self::PER_PAGE,
                'sources' => $sourceString
            ]);

        $data = $response->json();

        if ($response->failed()) {
            if (in_array(data_get($data, 'code'), [self::MAXIMUM_RESULT_REACHED, self::RATE_LIMITING])) {
                throw new MaximumArticleResultException();
            }

            throw new FailedToFetchArticleFromSourceException('Failed to fetch articles from News API with error .' . data_get($data, 'message'));
        }

        $articles = collect(data_get($data, 'articles', []))
            ->map(function (array $article) {
                return new ArticleDTO(
                    title: $article['title'],
                    author: $article['author'],
                    source: $article['source']['name'],
                    description: $article['description'],
                    url: $article['url'] ?? null,
                    published_at: $article['publishedAt'] ?? null,
                    content: $article['content'] ?? null,
                    image_url: $article['urlToImage'],
                    category: null,
                );
            })
            ->all();

        $collection = new ArticleCollection($articles);

        $totalPages = (int) ceil($data['totalResults'] / self::PER_PAGE);

        if ($page === $totalPages) {
            $collection->setIsLastPage(true);
        }

        return $collection;
    }
}
