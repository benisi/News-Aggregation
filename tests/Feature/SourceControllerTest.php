<?php

namespace Tests\Feature;

use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SourceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Source::factory()->create(['name' => 'Zero Source']);
        Source::factory()->create(['name' => 'Alpha Source']);
        Source::factory()->create(['name' => 'Beta Source']);
    }

    #[Test]
    public function it_returns_all_sources_ordered_by_name()
    {
        $response = $this->getJson(route('api.sources.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        $response->assertJsonPath('data.0.name', 'Alpha Source');
        $response->assertJsonPath('data.1.name', 'Beta Source');
        $response->assertJsonPath('data.2.name', 'Zero Source');
    }

    #[Test]
    public function source_index_is_cached_and_cleared_on_model_update()
    {
        $this->assertFalse(Cache::has(Source::CACHE_KEY));

        $this->getJson(route('api.sources.index'));
        $this->assertTrue(Cache::has(Source::CACHE_KEY));

        $newSource = Source::factory()->create(['name' => 'New Source']);

        $this->assertFalse(Cache::has(Source::CACHE_KEY));

        $this->getJson(route('api.sources.index'));
        $this->assertTrue(Cache::has(Source::CACHE_KEY));

        $newSource->delete();

        $this->assertFalse(Cache::has(Source::CACHE_KEY));
    }
}
