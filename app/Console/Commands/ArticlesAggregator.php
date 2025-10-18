<?php

namespace App\Console\Commands;

use App\Enums\DataSourceEnum;
use App\Jobs\AggregateArticle;
use Illuminate\Console\Command;

class ArticlesAggregator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:aggregate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'poll articles from various data sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (DataSourceEnum::cases() as $source) {
            $fetcher = new ($source->getFetcher());

            $bulkSources = $source->getSources();

            if ($bulkSources) {
                foreach ($source->getSources() as $sources) {
                    dispatch(new AggregateArticle($fetcher, $sources));
                }
            } else {
                dispatch(new AggregateArticle($fetcher));
            }
        }
    }
}
