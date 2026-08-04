<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Closes the account: every token is revoked, so the login stops working at
 * once, and the row is soft-deleted. Nothing is destroyed — signing back in
 * reopens it, whenever that is. See LoginController.
 *
 * The children are deliberately left untouched. They belong to the account
 * rather than to the deletion, and leaving them where they are is what makes
 * coming back a reopening rather than a rebuild.
 */
class DeleteAccountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();

        // The phone stops being reachable too, or a closed account carries on
        // getting told about a streak nobody is keeping.
        $user->devices()->delete();

        $user->delete();

        return ApiResponse::success(
            null,
            'Your account is closed. Sign in again any time to reopen it.',
        );
    }
}
