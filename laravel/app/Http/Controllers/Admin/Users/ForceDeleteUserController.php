<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Erases an account for good, and only one already inside its grace period —
 * closing it first is what makes this deliberate rather than a mis-click.
 *
 * The children they created go with it: created_by_user_id cascades in the
 * database, and every chapter, milestone, memory and trophy hangs off those
 * children. Children only shared with this account are left alone; the account
 * never owned them.
 */
class ForceDeleteUserController extends Controller
{
    public function __invoke(int $user): RedirectResponse
    {
        $account = User::onlyTrashed()->findOrFail($user);

        $name = $account->name;

        $account->tokens()->delete();

        $account->forceDelete();

        return back()->with('success', "{$name}'s account and everything recorded under it are gone.");
    }
}
