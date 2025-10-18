<?php

namespace App\Enums;

use App\Services\DataSources\DataFetcherInterface;
use App\Services\DataSources\GuardianApiFetcher;
use App\Services\DataSources\NewsApiFetcher;
use App\Services\DataSources\NYTimesApiFetcher;

enum DataSourceEnum: string
{
    case NEWSAPI = 'newsapi';
    case GUARDIAN = 'guardian';
    case NYTIMES = 'nytimes';

    /**
     * Get an array of all fetcher instances required to process this source.
     * This encapsulates the batching logic and dependency injection.
     *
     * @return DataFetcherInterface[]
     */
    public function getFetchers(): array
    {
        return match ($this) {
            self::NEWSAPI => $this->getNewsApiFetchers(),
            self::GUARDIAN => [app(GuardianApiFetcher::class)],
            self::NYTIMES => [app(NYTimesApiFetcher::class)],
        };
    }

    /**
     * A private helper method to build the batch-specific fetchers for NewsAPI.
     * @return NewsApiFetcher[]
     */
    private function getNewsApiFetchers(): array
    {
        $allSources = config('news-sources.newsapi_ids', []);
        if (empty($allSources)) {
            return [];
        }

        $sourceBatches = array_chunk($allSources, 20);

        $fetchers = [];
        foreach ($sourceBatches as $batch) {
            $fetchers[] = app(NewsApiFetcher::class, ['sources' => $batch]);
        }

        return $fetchers;
    }
}
