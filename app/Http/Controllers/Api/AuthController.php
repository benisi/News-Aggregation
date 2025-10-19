<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $registerUser): JsonResponse
    {
        $data = RegisterDTO::fromRequest($request);

        $result = $registerUser->execute($data);

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, LoginUserAction $loginUser): JsonResponse
    {
        $loginResult = $loginUser->execute(LoginDTO::fromRequest($request));

        return (new UserResource($loginResult['user']))
            ->additional(['token' => $loginResult['token']])
            ->response()
            ->setStatusCode(200);
    }

    public function logout(Request $request, LogoutUserAction $logoutUser): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $logoutUser->execute($user);

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }
}
