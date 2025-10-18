<?php

namespace Tests\Unit\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Exceptions\FailedToFetchArticleFromSourceException;
use App\Services\DataSources\NYTimesApiFetcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NYTimesApiFetcherTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_fetches_and_transforms_articles_correctly()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-18'));

        $fakeApiResponse = [
            'status' => 'OK',
            'response' => [
                'docs' => [
                    [
                        'abstract' => 'This is a test abstract.',
                        'web_url' => 'https://www.nytimes.com/test-article.html',
                        'lead_paragraph' => 'This is the leading paragraph.',
                        'source' => 'The New York Times',
                        'multimedia' => [
                            'default' => [
                                'url' => 'images/2025/10/18/test-image.jpg',
                            ]
                        ],
                        'headline' => ['main' => 'Test Headline'],
                        'pub_date' => '2025-10-18T12:00:00+0000',
                        'section_name' => 'Technology',
                        'byline' => ['original' => 'By John Doe'],
                    ],
                ],
                'meta' => [
                    'hits' => 50,
                    'offset' => 0,
                ],
            ],
        ];

        Http::fake([
            config('services.nytimes.url') . '*' => Http::response($fakeApiResponse),
        ]);

        $fetcher = app(NYTimesApiFetcher::class);
        $result = $fetcher->fetch(1);

        $this->assertInstanceOf(ArticleCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertFalse($result->getIsLastPage(), 'Should not be the last page when hits > offset + per_page.');

        /** @var ArticleDTO $articleDto */
        $articleDto = $result->first();
        $this->assertInstanceOf(ArticleDTO::class, $articleDto);

        $this->assertEquals('Test Headline', $articleDto->title);
        $this->assertEquals(['John Doe'], $articleDto->authors);
        $this->assertEquals('Technology', $articleDto->category);
        $this->assertEquals('https://www.nytimes.com/test-article.html', $articleDto->url);
    }

    #[Test]
    public function it_correctly_identifies_the_last_page()
    {

        $fakeApiResponse = [
            'status' => 'OK',
            'response' => [
                'docs' => [
                    [
                        'abstract' => 'This is a test abstract.',
                        'web_url' => 'https://www.nytimes.com/test-article.html',
                        'lead_paragraph' => 'This is the leading paragraph.',
                        'source' => 'The New York Times',
                        'multimedia' => [
                            'default' => [
                                'url' => 'images/2025/10/18/test-image.jpg',
                            ]
                        ],
                        'headline' => ['main' => 'Test Headline'],
                        'pub_date' => '2025-10-18T12:00:00+0000',
                        'section_name' => 'Technology',
                        'byline' => ['original' => 'By John Doe'],
                    ],
                ],
                'meta' => [
                    'hits' => 8,
                    'offset' => 0,
                ],
            ],
        ];

        Http::fake(['*' => Http::response($fakeApiResponse)]);
        $fetcher = app(NYTimesApiFetcher::class);

        $result = $fetcher->fetch(1);

        $this->assertTrue($result->getIsLastPage());
    }

    #[Test]
    public function it_throws_an_exception_on_api_failure()
    {
        $fakeErrorResponse = [
            'fault' => [
                'faultstring' => 'Invalid ApiKey',
            ]
        ];

        Http::fake(['*' => Http::response($fakeErrorResponse, 401)]);
        $fetcher = app(NYTimesApiFetcher::class);

        $this->expectException(FailedToFetchArticleFromSourceException::class);

        $fetcher->fetch(1);
    }
}
