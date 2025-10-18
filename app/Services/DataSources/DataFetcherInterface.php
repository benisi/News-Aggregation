<?php

namespace App\Services\DataSources;

use App\Collections\ArticleCollection;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use App\Exceptions\MaximumArticleResultException;

interface DataFetcherInterface
{
    /**
     * Fetch paginated articles from APIs.
     * @param int $page
     * @return ArticleCollection
     * @throws FailedToFetchArticleFromSourceException
     * @throws MaximumArticleResultException
     */
    public function fetch(int $page, array $sources = []): ArticleCollection;
}
