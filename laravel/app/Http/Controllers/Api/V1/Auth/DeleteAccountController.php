<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use App\Http\Responses\ApiResponse;
use App\Jobs\PurgeUserAccount;
use Illuminate\Http\JsonResponse;

/**
 * Deletes the account. Every token is revoked and the phone stops being
 * reachable, so nothing can be signed in with or told about a streak the
 * moment this returns.
 *
 * The rest — the children, the memories, the photos, the row itself — is
 * PurgeUserAccount's job, because a family album is a lot of files and the
 * parent has already been told it is gone. Nothing is kept back and nothing is
 * only marked as deleted: signing up again starts an empty account.
 */
class DeleteAccountController extends Controller
{
    public function __invoke(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->devices()->delete();

        PurgeUserAccount::dispatch($user->id);

        return ApiResponse::success(
            null,
            'Your account is being deleted. Everything on it goes with it.',
        );
    }
}
