<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\PurgeUserAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Erases an account for good, and only one already closed — closing it first is
 * what makes this deliberate rather than a mis-click.
 *
 * The same job the app's own Delete Account runs, so an account erased from
 * here leaves nothing on the disk either: the children they created, every
 * chapter, milestone, memory and photo. Children only shared with this account
 * are left alone; the account never owned them.
 */
class ForceDeleteUserController extends Controller
{
    public function __invoke(int $user): RedirectResponse
    {
        $account = User::onlyTrashed()->findOrFail($user);

        $name = $account->name;

        $account->tokens()->delete();

        PurgeUserAccount::dispatch($account->id);

        return back()->with('success', "{$name}'s account and everything recorded under it are gone.");
    }
}
