<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Source $preferredSource;
    protected Source $otherSource;
    protected Category $preferredCategory;
    protected Author $preferredAuthor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $this->preferredSource = Source::factory()->create(['name' => 'Preferred Source']);
        $this->otherSource = Source::factory()->create(['name' => 'Other Source']);
        $this->preferredCategory = Category::factory()->create(['name' => 'Preferred Category']);
        $this->preferredAuthor = Author::factory()->create(['name' => 'John Doe']);

        $article1 = Article::factory()->create([
            'title' => 'Article 1 - Preferred Source/Category',
            'source_id' => $this->preferredSource->id,
            'category_id' => $this->preferredCategory->id,
            'published_at' => now()->subHour(1),
        ]);

        $article1->authors()->attach($this->preferredAuthor->id);

        Article::factory()->create([
            'title' => 'Article 2 - Excluded Source',
            'source_id' => $this->otherSource->id,
            'category_id' => $this->preferredCategory->id,
            'published_at' => now()->subHour(2),
        ]);

        Article::factory()->create([
            'title' => 'Article 3 - Excluded Category',
            'source_id' => $this->preferredSource->id,
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subHour(3),
        ]);
    }

    protected function createPreferences(array $preferences): void
    {
        foreach ($preferences as $key => $value) {
            UserSetting::factory()->create([
                'user_id' => $this->user->id,
                'key' => $key,
                'value' => is_array($value) ? implode(',', $value) : $value,
            ]);
        }
    }

    #[Test]
    public function it_returns_only_articles_matching_all_saved_preferences(): void
    {
        $this->createPreferences([
            'preferred_sources' => [$this->preferredSource->id],
            'preferred_categories' => [$this->preferredCategory->id],
            'preferred_authors' => [$this->preferredAuthor->id],
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.user.feed.index'));

        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Article 1 - Preferred Source/Category');
    }

    #[Test]
    public function it_returns_no_articles_when_user_has_no_saved_preferences(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.user.feed.index'));

        $response->assertStatus(200);

        $response->assertJsonPath('meta.total', null);
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_combines_saved_preferences_with_dynamic_search_filters(): void
    {
        $this->createPreferences([
            'preferred_sources' => [$this->preferredSource->id],
        ]);

        Article::factory()->create([
            'title' => 'Rocket Launch Success',
            'source_id' => $this->preferredSource->id,
            'category_id' => $this->preferredCategory->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.user.feed.index', ['search' => 'Rocket']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Rocket Launch Success');
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->getJson(route('api.user.feed.index'))
            ->assertStatus(401);
    }
}
