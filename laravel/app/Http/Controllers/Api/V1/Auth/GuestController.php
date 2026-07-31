<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A user exists from first launch, before anybody has typed an email.
 *
 * The app calls this once, keeps the token, and later upgrades the same row
 * through /auth/register — so nothing has to be migrated when they sign up.
 */
class GuestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'in:en,sq'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        $user = User::create([
            'name' => $validated['name'] ?? 'Parent',
            'language' => Language::tryFrom($validated['language'] ?? '') ?? Language::English,
            'timezone' => $validated['timezone'] ?? 'UTC',
        ]);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 'Welcome.', 201);
    }
}
