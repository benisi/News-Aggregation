<?php

namespace Tests\Feature\Jobs;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Jobs\AggregateArticle;
use App\Models\Source;
use App\Models\SourceAlias;
use App\Services\DataSources\DataFetcherInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AggregateArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var DataFetcherInterface&MockInterface
     */
    protected $fetcherMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fetcherMock = $this->mock(DataFetcherInterface::class);

        Queue::fake();
    }

    #[Test]
    public function it_fetches_saves_articles_and_dispatches_the_next_job_for_pagination()
    {
        $source = Source::factory()->create();
        SourceAlias::factory()->create([
            'name' => $source->name,
            'source_id' => $source->id,
        ]);

        $fakeArticleDto = new ArticleDTO(
            title: 'Test Title',
            content: 'Test content.',
            source: $source->name,
            category: 'Technology',
            author: 'John Doe',
            description: 'A test description.',
            published_at: '2025-10-18T12:00:00Z',
            url: 'http://example.com/article-1',
            image_url: 'http://example.com/image.jpg'
        );

        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(false); // This is NOT the last page.

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->with(1, [])
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, [], 1);
        $job->handle();

        $this->assertDatabaseHas('articles', [
            'url' => 'http://example.com/article-1',
            'title' => 'Test Title',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'technology',
            'slug' => 'technology',
        ]);

        Queue::assertPushed(AggregateArticle::class, function (AggregateArticle $job) {
            $reflection = new \ReflectionClass($job);
            $pageProperty = $reflection->getProperty('page');
            return $pageProperty->getValue($job) === 2;
        });
    }

    #[Test]
    public function it_does_not_dispatch_a_new_job_if_it_is_the_last_page()
    {
        $source = Source::factory()->create();
        SourceAlias::factory()->create(['name' => $source->name]);
        $fakeArticleDto = new ArticleDTO('Title', 'Content', $source->name, 'Cat', 'Author', 'Desc', '2025-01-01', 'http://a.com', null);

        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(true); // This is the last page.

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, [], 1);
        $job->handle();

        Queue::assertNotPushed(AggregateArticle::class);
    }

    #[Test]
    public function it_skips_articles_when_source_alias_is_not_found()
    {
        $fakeArticleDto = new ArticleDTO('Title', 'Content', 'Unknown Source', 'Cat', 'Author', 'Desc', '2025-01-01', 'http://a.com', null);
        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(true);

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, [], 1);
        $job->handle();

        $this->assertDatabaseMissing('articles', [
            'url' => 'http://a.com',
        ]);
        
        Queue::assertNotPushed(AggregateArticle::class);
    }
}
