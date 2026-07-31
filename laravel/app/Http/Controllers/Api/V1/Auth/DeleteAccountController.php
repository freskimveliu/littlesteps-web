<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Soft-deletes the account and starts a 30-day grace period: the login stops
 * working immediately and the children go with it, but nothing is destroyed
 * until a scheduled job clears it. Signing back in inside the window restores
 * everything.
 */
class DeleteAccountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return ApiResponse::success([
            'recoverableUntil' => now()->addDays(30)->toIso8601String(),
        ], 'Your account is scheduled for deletion. Sign in within 30 days to undo it.');
    }
}
