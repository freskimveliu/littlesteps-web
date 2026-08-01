<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Puts credentials on the user who is already signed in, so everything they
 * recorded before signing up comes with them.
 */
class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $request->user() ?? new User(['name' => $request->string('name')->toString()]);

        $user->fill($request->safe()->only(['name', 'email', 'password']))->save();

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 'Account created.', 201);
    }
}
