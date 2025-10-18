<?php

namespace App\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GuardianApiFetcher implements DataFetcherInterface
{
    const SOURCE = 'The Guardian';

    public function fetch(int $page, array $sources = []): ArticleCollection
    {
        $daysToFetch = config('services.guardian.days_to_fetch', 1);
        $fromDate = Carbon::now()->subDays($daysToFetch)->format('Y-m-d');
        $toDate = Carbon::now()->format('Y-m-d');

        $response = Http::get(config('services.guardian.url'), [
            'api-key' => config('services.guardian.key'),
            'from-date' => $fromDate,
            'to-date'   => $toDate,
            'page' => $page,
            'page-size' => 100,
            'order-by' => 'newest',
            'show-fields' => 'bodyText,thumbnail',
            'show-tags' => 'contributor',
        ]);

        $data = $response->json('response');

        if ($response->failed()) {
            throw new FailedToFetchArticleFromSourceException('Failed to fetch articles from The Guardian API: ' . data_get($data, 'message'));
        }

        $articles = collect(data_get($data, 'results', []))
            ->map(function (array $article) {
                $authorTag = collect(data_get($article, 'tags', []))
                    ->firstWhere('type', 'contributor');

                return new ArticleDTO(
                    title: data_get($article, 'webTitle'),
                    author: $authorTag ? $authorTag['webTitle'] : 'Guardian staff',
                    source: self::SOURCE,
                    description: Str::limit(strip_tags(data_get($article, 'fields.bodyText')), 250),
                    url: data_get($article, 'webUrl'),
                    published_at: data_get($article, 'webPublicationDate'),
                    content: data_get($article, 'fields.bodyText'),
                    image_url: data_get($article, 'fields.thumbnail'),
                    category: data_get($article, 'sectionName'),
                );
            })
            ->all();

        $collection = new ArticleCollection($articles);

        if (data_get($data, 'currentPage') === data_get($data, 'pages')) {
            $collection->setIsLastPage(true);
        }

        return $collection;
    }
}
