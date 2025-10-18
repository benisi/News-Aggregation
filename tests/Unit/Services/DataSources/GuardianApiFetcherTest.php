<?php

namespace Tests\Unit\Services\DataSources;

use App\Collections\ArticleCollection;
use App\DTOs\ArticleDTO;
use App\Services\DataSources\GuardianApiFetcher;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuardianApiFetcherTest extends TestCase
{
    #[Test]
    public function it_fetches_and_transforms_articles_correctly()
    {
        $fakeApiResponse = [
            'response' => [
                'status' => 'ok',
                'currentPage' => 1,
                'pages' => 1,
                'results' => [
                    [
                        'webTitle' => 'Test Article Title',
                        'webUrl' => 'https://www.theguardian.com/test-article',
                        'webPublicationDate' => '2025-10-18T12:00:00Z',
                        'sectionName' => 'Technology',
                        'fields' => [
                            'bodyText' => 'This is the body content of the test article.',
                            'thumbnail' => 'https://media.guim.co.uk/test-image.jpg',
                        ],
                        'tags' => [
                            ['type' => 'contributor', 'webTitle' => 'Jane Doe']
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'content.guardianapis.com/*' => Http::response($fakeApiResponse),
        ]);

        $fetcher = new GuardianApiFetcher();
        $result = $fetcher->fetch(1);

        $this->assertInstanceOf(ArticleCollection::class, $result, 'The result should be an ArticleCollection.');
        $this->assertCount(1, $result, 'The collection should contain one article.');
        $this->assertTrue($result->getIsLastPage(), 'The collection should be marked as the last page.');

        /** @var ArticleDTO $articleDto */
        $articleDto = $result->first();
        $this->assertInstanceOf(ArticleDTO::class, $articleDto, 'The item in the collection should be an ArticleDTO.');

        $this->assertEquals('Test Article Title', $articleDto->title);
        $this->assertEquals('Jane Doe', $articleDto->author);
        $this->assertEquals('The Guardian', $articleDto->source);
        $this->assertEquals('Technology', $articleDto->category);
        $this->assertEquals('https://www.theguardian.com/test-article', $articleDto->url);
    }
}
