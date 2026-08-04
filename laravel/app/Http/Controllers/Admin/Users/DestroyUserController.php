<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Closes an account the same way the app itself does — tokens revoked, devices
 * unhooked, the row soft-deleted. Nothing is destroyed: the children stay where
 * they are and RestoreUserController brings all of it back. See
 * Api\V1\Auth\DeleteAccountController, which this mirrors.
 */
class DestroyUserController extends Controller
{
    public function __invoke(Request $request, int $user): RedirectResponse
    {
        $account = User::findOrFail($user);

        // Nobody closes the account they are signed in with, which is also what
        // keeps the last admin from locking everyone out of this console: any
        // other admin they close still leaves the one doing the closing.
        if ($account->is($request->user())) {
            return back()->with('error', 'This is the account you are signed in with.');
        }

        $account->tokens()->delete();
        $account->devices()->delete();

        $account->delete();

        return back()->with('success', "{$account->name}'s account is scheduled for deletion.");
    }
}
