<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $sFox;
    protected $sCnn;
    protected $aLuca;
    protected $aJane;
    protected $aTom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sFox = Source::factory()->create(['name' => 'FOX News']);
        $this->sCnn = Source::factory()->create(['name' => 'CNN']);

        $this->aLuca = Author::factory()->create(['name' => 'Luca John', 'source_id' => $this->sFox->id]);
        $this->aJane = Author::factory()->create(['name' => 'Jane Smith', 'source_id' => $this->sCnn->id]);
        $this->aTom = Author::factory()->create(['name' => 'Tom Cruise', 'source_id' => $this->sFox->id]);

        $sOthers = Source::factory()->create(['name' => 'Background Source']);
        Author::factory()
            ->count(100)
            ->sequence(fn($sequence) => [
                'name' => 'Background Author ' . ($sequence->index + 1),
            ])
            ->create(['source_id' => $sOthers->id]);
    }

    #[Test]
    public function it_returns_limited_results_when_no_search_term_is_provided()
    {
        $response = $this->getJson(route('api.authors.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(50, 'data');
    }

    #[Test]
    public function it_applies_per_page_sanitation_and_capping()
    {
        $response = $this->getJson(route('api.authors.index', ['per_page' => 150]));
        $response->assertJsonPath('meta.per_page', 100);
        $response->assertJsonCount(100, 'data');

        $response = $this->getJson(route('api.authors.index', ['per_page' => 'bad_input']));
        $response->assertJsonPath('meta.per_page', 50);

        $response = $this->getJson(route('api.authors.index', ['per_page' => -10]));
        $response->assertJsonPath('meta.per_page', 50);
    }

    #[Test]
    public function it_filters_by_author_name_only_using_or_logic()
    {
        $response = $this->getJson(route('api.authors.index', ['search' => 'luca']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Luca John');
    }

    #[Test]
    public function it_filters_by_source_name_only_using_or_logic()
    {
        $response = $this->getJson(route('api.authors.index', ['search' => 'CNN']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Jane Smith');
    }

    #[Test]
    public function it_combines_name_and_source_search_terms_using_or_logic()
    {
        $response = $this->getJson(route('api.authors.index', ['search' => 'Fox']));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Luca John'));
        $this->assertTrue($names->contains('Tom Cruise'));
    }

    #[Test]
    public function it_applies_case_insensitive_or_search()
    {
        $response = $this->getJson(route('api.authors.index', ['search' => 'fox']));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Luca John'));
        $this->assertTrue($names->contains('Tom Cruise'));
    }

    #[Test]
    public function it_returns_no_results_for_a_non_matching_term()
    {
        $response = $this->getJson(route('api.authors.index', ['search' => 'ZXY']));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
