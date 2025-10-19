<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Category::factory()->create(['name' => 'Zero Category']);
        Category::factory()->create(['name' => 'Alpha Category']);
        Category::factory()->create(['name' => 'Beta Category']);
    }

    #[Test]
    public function it_returns_all_categories_ordered_by_name()
    {
        $response = $this->getJson(route('api.categories.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        $response->assertJsonPath('data.0.name', 'Alpha Category');
        $response->assertJsonPath('data.1.name', 'Beta Category');
        $response->assertJsonPath('data.2.name', 'Zero Category');
    }

    #[Test]
    public function category_index_is_cached_and_cleared_on_model_update()
    {
        $this->assertFalse(Cache::has(Category::CACHE_KEY));

        $this->getJson(route('api.categories.index'));
        $this->assertTrue(Cache::has(Category::CACHE_KEY));

        $newCategory = Category::factory()->create(['name' => 'Another Category']);

        $this->assertFalse(Cache::has(Category::CACHE_KEY));

        $this->getJson(route('api.categories.index'));
        $this->assertTrue(Cache::has(Category::CACHE_KEY));

        $newCategory->delete();

        $this->assertFalse(Cache::has(Category::CACHE_KEY));
    }
}
