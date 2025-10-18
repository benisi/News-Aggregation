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

    /** @return class-string<DataFetcherInterface> */
    public function getFetcher(): string
    {
        return match ($this) {
            self::NEWSAPI => NewsApiFetcher::class,
            self::GUARDIAN => GuardianApiFetcher::class,
            self::NYTIMES => NYTimesApiFetcher::class
        };
    }

    public function getSources(): array|false
    {
        return match ($this) {
            self::NEWSAPI => array_chunk(config('news-sources.newsapi_ids'), 20),
            default => false
        };
    }
}
