<?php

namespace App\Http\Controllers\Api;

use App\Actions\Users\GetPreferencesAction;
use App\Actions\Users\StorePreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreferencesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getPreferences(Request $request, GetPreferencesAction $action): JsonResponse
    {
        $preferences = $action->execute($request->user());

        return response()->json([
            'data' => $preferences,
        ]);
    }

    public function storePreferences(StorePreferencesRequest $request, StorePreferencesAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated());

        $updatedSettings = app(GetPreferencesAction::class)->execute($request->user());

        return response()->json([
            'message' => 'User preferences updated successfully.',
            'data' => $updatedSettings,
        ], 200);
    }
}
