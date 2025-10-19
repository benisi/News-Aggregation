<?php

namespace App\Actions\Auth;

use App\Concerns\CreatesApiTokens;
use App\DTOs\RegisterDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    use CreatesApiTokens;

    public function execute(RegisterDTO $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $token = $this->createApiToken($user);

            return ['user' => $user, 'token' => $token];
        });
    }
}
