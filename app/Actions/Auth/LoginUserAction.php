<?php

namespace App\Actions\Auth;

use App\DTOs\LoginDTO;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    const TOKEN_NAME = 'frontend_access_token';

    public function execute(LoginDTO $data): array
    {
        if (!Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            throw ValidationException::withMessages([
                'email' => [__('Invalid credentials provided.')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
