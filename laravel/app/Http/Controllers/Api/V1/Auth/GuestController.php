<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GuestRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * A user exists from first launch, before anybody has typed an email.
 *
 * The app calls this once, keeps the token, and later upgrades the same row
 * through /auth/register — so nothing has to be migrated when they sign up.
 */
class GuestController extends Controller
{
    public function __invoke(GuestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::open([
            'name' => $validated['name'] ?? 'User',
            'language' => Language::tryFrom($validated['language'] ?? '') ?? Language::English,
            'timezone' => $validated['timezone'] ?? 'UTC',
        ]);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 'Welcome.', 201);
    }
}
