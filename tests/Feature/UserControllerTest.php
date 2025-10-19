<?php

namespace Tests\Feature\Api;

use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_get_user_preferences(): void
    {
        UserSetting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'preferred_sources',
            'value' => '1,5,10',
        ]);
        UserSetting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'preferred_categories',
            'value' => '2,6',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.user.preferences.show'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'preferred_sources' => '1,5,10',
                    'preferred_categories' => '2,6',
                ],
            ]);
    }

    #[Test]
    public function it_returns_empty_data_when_user_has_no_preferences(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.user.preferences.show'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function it_can_store_user_preferences(): void
    {
        $sources = Source::factory()->count(2)->create();
        $categories = Category::factory()->count(3)->create();

        $payload = [
            'preferred_sources' => $sources->pluck('id')->toArray(),
            'preferred_categories' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->user)->postJson(route('api.user.preferences.store'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User preferences updated successfully.',
                'data' => [
                    'preferred_sources' => implode(',', $payload['preferred_sources']),
                    'preferred_categories' => implode(',', $payload['preferred_categories']),
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'preferred_sources',
            'value' => implode(',', $payload['preferred_sources']),
        ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'preferred_categories',
            'value' => implode(',', $payload['preferred_categories']),
        ]);
    }

    #[Test]
    public function it_updates_existing_preferences_instead_of_creating_new_ones(): void
    {
        UserSetting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'preferred_authors',
            'value' => '1,2',
        ]);
        $authors = Author::factory()->count(2)->create();
        $payload = ['preferred_authors' => $authors->pluck('id')->toArray()];

        $this->actingAs($this->user)->postJson(route('api.user.preferences.store'), $payload);

        $this->assertDatabaseCount('user_settings', 1);
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'preferred_authors',
            'value' => implode(',', $payload['preferred_authors']),
        ]);
    }

    #[Test]
    public function store_preferences_fails_validation_if_payload_is_empty(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.user.preferences.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_sources', 'preferred_categories', 'preferred_authors']);
    }

    #[Test]
    public function store_preferences_fails_validation_if_ids_do_not_exist(): void
    {
        $payload = ['preferred_sources' => [999]];

        $response = $this->actingAs($this->user)->postJson(route('api.user.preferences.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_sources.0']);
    }

    #[Test]
    public function store_preferences_fails_validation_if_value_is_not_an_array(): void
    {
        $payload = ['preferred_categories' => 'not-an-array'];

        $response = $this->actingAs($this->user)->postJson(route('api.user.preferences.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_categories']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_endpoints(): void
    {
        $this->getJson(route('api.user.preferences.show'))->assertStatus(401);

        $this->postJson(route('api.user.preferences.store'), [])->assertStatus(401);
    }
}
