<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;

class StorePreferencesAction
{
    public function execute(User $user, array $settingsToSave): void
    {
        DB::transaction(function () use ($user, $settingsToSave) {
            foreach ($settingsToSave as $key => $rawValue) {
                $key = (string) $key;
                $value = is_array($rawValue) ? implode(',', $rawValue) : $rawValue;

                UserSetting::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        });
    }
}
