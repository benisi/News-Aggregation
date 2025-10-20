<?php

namespace App\Console\Commands;

use App\Enums\DataSourceEnum;
use App\Jobs\AggregateArticle;
use Illuminate\Console\Command;

class ArticlesAggregator extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'articles:aggregate {source?}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Dispatches jobs to poll articles from various data sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceArgument = $this->argument('source');
        $sourcesToProcess = [];

        if ($sourceArgument) {
            $sourceEnum = DataSourceEnum::tryFrom($sourceArgument);
            if (!$sourceEnum) {
                $this->error("Invalid source '{$sourceArgument}'.");
                $availableSources = implode(',', array_map(fn($case) => $case->value, DataSourceEnum::cases()));
                $this->line("Available sources: {$availableSources}");
                return self::FAILURE;
            }
            $sourcesToProcess = [$sourceEnum];
        } else {
            $sourcesToProcess = DataSourceEnum::cases();
        }

        foreach ($sourcesToProcess as $source) {
            $fetchers = $source->getFetchers();
            foreach ($fetchers as $fetcher) {
                dispatch(new AggregateArticle($fetcher));
            }
        }

        $this->info('Article aggregation jobs have been dispatched!');
        return self::SUCCESS;
    }
}
