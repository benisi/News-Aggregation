<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_register_successfully(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.auth.register'), $userData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
                'token',
            ])
            ->assertJson([
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);
    }

    #[Test]
    #[DataProvider('invalidRegistrationDataProvider')]
    public function it_returns_validation_errors_for_invalid_registration_data(array $payload, array|string $errors): void
    {
        $this->postJson(route('api.auth.register'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    #[Test]
    public function it_returns_an_error_if_the_email_is_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson(route('api.auth.register'), [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token'])
            ->assertJsonPath('data.email', $user->email);
    }

    #[Test]
    public function it_rejects_login_with_incorrect_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->postJson(route('api.auth.logout'));

        $response
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function an_unauthenticated_user_cannot_log_out(): void
    {
        $this->postJson(route('api.auth.logout'))
            ->assertStatus(401);
    }

    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'missing name' => [['email' => 'test@test.com', 'password' => 'password', 'password_confirmation' => 'password'], 'name'],
            'missing email' => [['name' => 'Test User', 'password' => 'password', 'password_confirmation' => 'password'], 'email'],
            'invalid email' => [['name' => 'Test User', 'email' => 'not-an-email', 'password' => 'password', 'password_confirmation' => 'password'], 'email'],
            'password too short' => [['name' => 'Test User', 'email' => 'test@test.com', 'password' => '123', 'password_confirmation' => '123'], 'password'],
            'password confirmation mismatch' => [['name' => 'Test User', 'email' => 'test@test.com', 'password' => 'password123', 'password_confirmation' => 'password456'], 'password'],
        ];
    }
}