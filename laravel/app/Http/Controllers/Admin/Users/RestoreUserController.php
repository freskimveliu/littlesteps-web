<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Undoes an account deletion inside its 30-day grace period. Everything comes
 * back with it — the children were never destroyed, only hidden with the user.
 */
class RestoreUserController extends Controller
{
    public function __invoke(int $user): RedirectResponse
    {
        User::onlyTrashed()->findOrFail($user)->restore();

        return back()->with('success', 'Account restored.');
    }
}
