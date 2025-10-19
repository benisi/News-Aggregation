<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Models\UserSetting;

class GetPreferencesAction
{
    public function execute(User $user): array
    {
        $settings = UserSetting::where('user_id', $user->id)
            ->pluck('value', 'key')
            ->toArray();

        return $settings;
    }
}
