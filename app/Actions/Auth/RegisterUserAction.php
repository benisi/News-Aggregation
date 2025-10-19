<?php

namespace App\Actions\Auth;

use App\DTOs\RegisterDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    const TOKEN_NAME = 'frontend_access_token';

    public function execute(RegisterDTO $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }
}
