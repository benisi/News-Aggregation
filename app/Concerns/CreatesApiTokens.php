<?php

namespace App\Concerns;

use App\Models\User;

trait CreatesApiTokens
{
    protected const SANCTUM_TOKEN_NAME = 'frontend_access_token';

    public function createApiToken(User $user): string
    {
        return $user->createToken(
            name: self::SANCTUM_TOKEN_NAME,
            expiresAt: now()->addMinutes((int) config('sanctum.expiration'))
        )->plainTextToken;
    }

    public function getApiTokenName(): string
    {
        return self::SANCTUM_TOKEN_NAME;
    }
}
