<?php

namespace Tests\Feature\Actions\Articles;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListArticlesActionTest extends TestCase
{
    use RefreshDatabase;

    protected $source1;
    protected $source2;
    protected $category1;
    protected $category2;
    protected $author1;
    protected $author2;
    protected $article1;
    protected $article2;
    protected $article3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source1 = Source::factory()->create(['name' => 'BBC News']);
        $this->source2 = Source::factory()->create(['name' => 'CNN']);
        $this->category1 = Category::factory()->create(['name' => 'Technology']);
        $this->category2 = Category::factory()->create(['name' => 'Politics']);
        $this->author1 = Author::factory()->create(['name' => 'John Doe', 'source_id' => $this->source1->id]);
        $this->author2 = Author::factory()->create(['name' => 'Jane Smith', 'source_id' => $this->source2->id]);

        $this->article1 = Article::factory()->create([
            'title' => 'The Future of Laravel',
            'source_id' => $this->source1->id,
            'category_id' => $this->category1->id,
            'published_at' => now()->subHours(1),
        ]);
        $this->article1->authors()->attach($this->author1->id);

        $this->article2 = Article::factory()->create([
            'title' => 'Political Turmoil',
            'source_id' => $this->source2->id,
            'category_id' => $this->category2->id,
            'published_at' => now()->subDays(2),
        ]);
        $this->article2->authors()->attach($this->author2->id);

        $this->article3 = Article::factory()->create([
            'title' => 'Ancient Technology Re-discovered',
            'description' => 'A search term match.',
            'source_id' => $this->source1->id,
            'category_id' => $this->category2->id,
            'published_at' => now()->subDays(5),
        ]);
        $this->article3->authors()->attach($this->author1->id);

        $source3 = Source::factory()->create(['name' => 'Other']);
        $category3 = Category::factory()->create(['name' => 'Other']);
        Article::factory()->count(16)->create([
            'published_at' => now()->subDays(10),
            'source_id' => $source3->id,
            'category_id' => $category3->id,
        ]);
    }

    #[Test]
    public function it_returns_paginated_articles_and_applies_default_ordering()
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200);

        // Assert default pagination (15 items) and total count
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.total', 19);

        // Assert ordering is correct (latest first)
        $response->assertJsonPath('data.0.id', $this->article1->id);
    }

    #[Test]
    public function it_filters_by_a_single_source_id_correctly()
    {
        // Query for only articles from Source 2 (CNN)
        $response = $this->getJson("/api/articles?source_id={$this->source2->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->article2->id);
    }

    #[Test]
    public function it_filters_by_multiple_categories_correctly()
    {
        // Article 1 is Category 1, Article 3 is Category 2
        $response = $this->getJson("/api/articles?category_id={$this->category1->id},{$this->category2->id}");

        $response->assertStatus(200);
        // Expect 3 articles (1, 2, 3) plus any others created in those categories
        $response->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_filters_by_author_id_correctly()
    {
        // Query for articles by Author 2 (Jane Smith, only wrote Article 2)
        $response = $this->getJson("/api/articles?author_id={$this->author2->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->article2->id);
    }

    #[Test]
    public function it_applies_full_text_search_across_fields()
    {
        // Search term "search term match." is only in Article 3's description
        $response = $this->getJson("/api/articles?search=search term match.");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->article3->id);
    }

    #[Test]
    public function it_applies_date_range_filter_correctly()
    {
        // Article 1 is 1 hour ago. Article 3 is 5 days ago.
        // Filter for articles newer than 1 day ago. Should only return Article 1.
        $startDate = now()->subDays(1)->format('Y-m-d');
        $response = $this->getJson("/api/articles?date_from={$startDate}");

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $this->article1->id);
    }

    #[Test]
    public function it_applies_per_page_limit_correctly()
    {
        $response = $this->getJson('/api/articles?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.per_page', 5);
    }

    #[Test]
    public function it_returns_default_per_page_on_invalid_input()
    {
        // Invalid input should fall back to the default of 15, as defined in the DTO
        $response = $this->getJson('/api/articles?per_page=iiriririr');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonCount(15, 'data');
    }
}
