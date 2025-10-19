<?php

namespace Tests\Feature\Jobs;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Jobs\AggregateArticle;
use App\Models\Article;
use App\Models\Author;
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
            authors: ['John Doe', 'Jane Smith'],
            description: 'A test description.',
            published_at: '2025-10-18T12:00:00Z',
            url: 'http://example.com/article-1',
            image_url: 'http://example.com/image.jpg'
        );

        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(false); // This is NOT the last page.

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->with(1)
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, 1);
        $job->handle();

        $this->assertDatabaseHas('articles', [
            'url' => 'http://example.com/article-1',
            'title' => 'Test Title',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'technology',
            'slug' => 'technology',
        ]);

        $this->assertDatabaseHas('authors', ['name' => 'John Doe', 'source_id' => $source->id]);
        $this->assertDatabaseHas('authors', ['name' => 'Jane Smith', 'source_id' => $source->id]);

        $article = Article::first();
        $author1 = Author::where('name', 'John Doe')->first();
        $author2 = Author::where('name', 'Jane Smith')->first();
        $this->assertDatabaseHas('article_author', ['article_id' => $article->id, 'author_id' => $author1->id]);
        $this->assertDatabaseHas('article_author', ['article_id' => $article->id, 'author_id' => $author2->id]);

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
        $fakeArticleDto = new ArticleDTO('Title', 'Content', $source->name, 'Cat', ['Author'], 'Desc', '2025-01-01', 'http://a.com', null);

        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(true); // This is the last page.

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, 1);
        $job->handle();

        Queue::assertNotPushed(AggregateArticle::class);
    }

    #[Test]
    public function it_skips_articles_when_source_alias_is_not_found()
    {
        $fakeArticleDto = new ArticleDTO('Title', 'Content', 'Unknown Source', 'Cat', ['Author'], 'Desc', '2025-01-01', 'http://a.com', null);
        $articleCollection = new ArticleCollection([$fakeArticleDto]);
        $articleCollection->setIsLastPage(true);

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($articleCollection);

        $job = new AggregateArticle($this->fetcherMock, 1);
        $job->handle();

        $this->assertDatabaseMissing('articles', [
            'url' => 'http://a.com',
        ]);

        Queue::assertNotPushed(AggregateArticle::class);
    }

    #[Test]
    public function it_syncs_authors_and_removes_old_associations_on_update()
    {
        $source = Source::factory()->create();
        SourceAlias::factory()->create(['name' => $source->name, 'source_id' => $source->id]);

        $authorA = Author::factory()->create(['name' => 'Author A', 'source_id' => $source->id]);
        $authorB = Author::factory()->create(['name' => 'Author B', 'source_id' => $source->id]);
        $authorC = Author::factory()->create(['name' => 'Author C', 'source_id' => $source->id]);

        $initialArticle = Article::factory()->create(['url' => 'http://example.com/update-test', 'source_id' => $source->id]);
        $initialArticle->authors()->sync([$authorA->id, $authorB->id]);

        $this->assertDatabaseHas('article_author', ['article_id' => $initialArticle->id, 'author_id' => $authorA->id]);
        $this->assertDatabaseHas('article_author', ['article_id' => $initialArticle->id, 'author_id' => $authorB->id]);
        $this->assertDatabaseMissing('article_author', ['article_id' => $initialArticle->id, 'author_id' => $authorC->id]);

        $updateDto = new ArticleDTO(
            title: 'Updated Title',
            content: 'New content.',
            source: $source->name,
            category: 'Technology',
            authors: ['Author B', 'Author C'],
            description: 'Updated description.',
            published_at: '2025-10-18T12:00:00Z',
            url: 'http://example.com/update-test',
            image_url: 'http://example.com/image.jpg'
        );
        $updateCollection = new ArticleCollection([$updateDto]);
        $updateCollection->setIsLastPage(true);

        $this->fetcherMock
            ->shouldReceive('fetch')
            ->with(1)
            ->once()
            ->andReturn($updateCollection);

        $job = new AggregateArticle($this->fetcherMock, 1);
        $job->handle();

        $updatedArticle = Article::where('url', 'http://example.com/update-test')->first();
        $updatedAuthorC = Author::where('name', 'Author C')->first();

        $this->assertDatabaseMissing('article_author', ['article_id' => $updatedArticle->id, 'author_id' => $authorA->id]);
        $this->assertDatabaseHas('article_author', ['article_id' => $updatedArticle->id, 'author_id' => $authorB->id]);
        $this->assertDatabaseHas('article_author', ['article_id' => $updatedArticle->id, 'author_id' => $updatedAuthorC->id]);

        $this->assertEquals('Updated Title', $updatedArticle->title);
    }
}
