<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Also the way back from a closed account: this looks through the soft delete,
 * so a parent who changed their mind signs in as normal and finds everything
 * where they left it. Nothing expires, so there is no wrong day to come back on.
 */
class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withTrashed()->where('email', $validated['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match our records.',
            ]);
        }

        $reopened = $user->trashed();

        if ($reopened) {
            $user->restore();
        }

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], $reopened ? 'Welcome back. Your account is open again.' : 'Welcome back.');
    }
}
