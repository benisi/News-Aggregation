<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\DataSourceEnum;
use App\Jobs\AggregateArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticlesAggregatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dispatches_an_aggregation_job_for_each_data_source()
    {
        Queue::fake();

        $this->artisan('articles:aggregate')->assertExitCode(0);

        $pushTimes = 0;

        foreach (DataSourceEnum::cases() as $source) {
            $sourceBatches = $source->getSources();
            if ($sourceBatches) {
                $pushTimes += count($sourceBatches);
            } else {
                $pushTimes++;
            }
        }

        Queue::assertPushed(AggregateArticle::class, $pushTimes);

        foreach (DataSourceEnum::cases() as $source) {
            Queue::assertPushed(function (AggregateArticle $job) use ($source) {
                $reflection = new \ReflectionClass($job);
                $fetcherProperty = $reflection->getProperty('fetcher');
                $fetcherInstance = $fetcherProperty->getValue($job);

                return $fetcherInstance instanceof ($source->getFetcher());
            });
        }
    }
}
