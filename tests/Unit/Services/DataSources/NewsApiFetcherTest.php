<?php

namespace Tests\Unit\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use App\Exceptions\MaximumArticleResultException;
use App\Services\DataSources\NewsApiFetcher;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsApiFetcherTest extends TestCase
{
    #[Test]
    public function it_fetches_and_transforms_articles_correctly_on_a_successful_request()
    {
        $fakeApiResponse = [
            'status' => 'ok',
            'totalResults' => 95,
            'articles' => [
                [
                    'source' => ['id' => 'cnn', 'name' => 'CNN'],
                    'author' => 'John Doe',
                    'title' => 'Breaking News: A Test Happened',
                    'description' => 'This is a test description for the breaking news.',
                    'url' => 'https://www.cnn.com/test-article',
                    'urlToImage' => 'https://www.cnn.com/test-image.jpg',
                    'publishedAt' => '2025-10-18T10:00:00Z',
                    'content' => 'This is the full content of the article.',
                ],
            ],
        ];

        Http::fake([
            config('services.newsapi.endpoint') . '*' => Http::response($fakeApiResponse, 200),
        ]);

        $fetcher = new NewsApiFetcher(['cnn']);
        $result = $fetcher->fetch(1);

        $this->assertInstanceOf(ArticleCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->getIsLastPage(), 'Since totalResults < PER_PAGE, it should be the last page.');

        /** @var ArticleDTO $articleDto */
        $articleDto = $result->first();
        $this->assertInstanceOf(ArticleDTO::class, $articleDto);

        $this->assertEquals('Breaking News: A Test Happened', $articleDto->title);
        $this->assertEquals('John Doe', $articleDto->author);
        $this->assertEquals('CNN', $articleDto->source);
        $this->assertEquals('This is a test description for the breaking news.', $articleDto->description);
        $this->assertEquals('https://www.cnn.com/test-article', $articleDto->url);
    }

    #[Test]
    public function it_throws_maximum_article_result_exception_on_api_limit_error()
    {
        $fakeErrorResponse = [
            'status' => 'error',
            'code' => 'maximumResultsReached',
            'message' => 'You have requested too many results. Developer accounts are limited to 100 results.',
        ];

        Http::fake([
            config('services.newsapi.endpoint') . '*' => Http::response($fakeErrorResponse, 429),
        ]);

        $fetcher = new NewsApiFetcher(['cnn']);

        $this->expectException(MaximumArticleResultException::class);

        $fetcher->fetch(1);
    }

    #[Test]
    public function it_throws_failed_to_fetch_article_exception_on_a_generic_api_failure()
    {
        $fakeErrorResponse = [
            'status' => 'error',
            'code' => 'apiKeyInvalid',
            'message' => 'Your API key is invalid or incorrect.',
        ];

        Http::fake([
            config('services.newsapi.endpoint') . '*' => Http::response($fakeErrorResponse, 401),
        ]);

        $fetcher = new NewsApiFetcher(['cnn']);

        $this->expectException(FailedToFetchArticleFromSourceException::class);

        $fetcher->fetch(1);
    }
}
