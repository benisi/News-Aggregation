<?php

namespace App\Enums;

use App\Services\DataSources\DataFetcherInterface;
use App\Services\DataSources\GuardianApiFetcher;
use App\Services\DataSources\NewsApiFetcher;

enum DataSourceEnum: string
{
    case NEWSAPI = 'newsapi';
    case GUARDIAN = 'guardian';

    /** @return class-string<DataFetcherInterface> */
    public function getFetcher(): string
    {
        return match ($this) {
            self::NEWSAPI => NewsApiFetcher::class,
            self::GUARDIAN => GuardianApiFetcher::class
        };
    }
}
