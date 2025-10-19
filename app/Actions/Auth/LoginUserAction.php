<?php

namespace App\Actions\Auth;

use App\Concerns\CreatesApiTokens;
use App\DTOs\LoginDTO;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    use CreatesApiTokens;

    public function execute(LoginDTO $data): array
    {
        if (!Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            throw ValidationException::withMessages([
                'email' => [__('Invalid credentials provided.')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('name', $this->getApiTokenName())->delete();

        $token = $this->createApiToken($user);

        return ['user' => $user, 'token' => $token];
    }
}
