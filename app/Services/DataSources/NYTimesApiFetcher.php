<?php

namespace App\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NYTimesApiFetcher implements DataFetcherInterface
{
    const SOURCE = 'The New York Times';
    const ARTICLES_PER_PAGE = 10;

    public function fetch(int $page, array $sources = []): ArticleCollection
    {
        $apiPage = $page > 0 ? $page - 1 : 0;
        $daysToFetch = config('services.nytimes.days_to_fetch', 1);
        $fromDate = Carbon::now()->subDays($daysToFetch)->format('Ymd');

        $response = Http::get(config('services.nytimes.url'), [
            'api-key' => config('services.nytimes.key'),
            'page' => $apiPage,
            'sort' => 'newest',
            'begin_date' => $fromDate
        ]);

        $data = $response->json();

        if ($response->failed()) {
            $errorMessage = data_get($data, 'fault.faultstring') ?? data_get($data, 'message', 'Unknown error');
            throw new FailedToFetchArticleFromSourceException('Failed to fetch articles from NYT API: ' . $errorMessage);
        }

        $articles = collect(data_get($data, 'response.docs', []))
            ->map(function (array $article) {
                $imageUrl = data_get($article, 'multimedia.default.url');

                $author = Str::of(data_get($article, 'byline.original', ''))
                    ->after('By ')
                    ->title()
                    ->whenEmpty(fn() => self::SOURCE . ' Staff');

                return new ArticleDTO(
                    title: data_get($article, 'headline.main'),
                    author: $author,
                    source: self::SOURCE,
                    description: data_get($article, 'abstract'),
                    url: data_get($article, 'web_url'),
                    published_at: data_get($article, 'pub_date'),
                    content: data_get($article, 'lead_paragraph'),
                    image_url: $imageUrl,
                    category: data_get($article, 'section_name'),
                );
            })
            ->all();

        $collection = new ArticleCollection($articles);

        $meta = data_get($data, 'response.meta', []);
        $totalHits = data_get($meta, 'hits', 0);
        $offset = data_get($meta, 'offset', 0);

        if (($offset + self::ARTICLES_PER_PAGE) >= $totalHits) {
            $collection->setIsLastPage(true);
        }

        return $collection;
    }
}
