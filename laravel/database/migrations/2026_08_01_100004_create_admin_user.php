<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the first admin from the environment.
 *
 * There is no registration route on the web side — /admin/login is the only
 * door — so a freshly deployed database has no account that can open it. This
 * does the job the tinker snippet in DEPLOYMENT.md used to do, except a deploy
 * performs it unattended and cannot get is_admin wrong: the flag is not in the
 * model's Fillable list, so User::create() by hand silently leaves it false
 * and the new account meets a 403.
 *
 * The credentials are read from the environment and never written here — this
 * repository is public. With ADMIN_EMAIL or ADMIN_PASSWORD unset the migration
 * does nothing at all, which is what keeps it out of local development and the
 * test suite (phpunit.xml pins both empty so a stray shell variable cannot
 * reach a test run).
 */
return new class extends Migration
{
    public function up(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            return;
        }

        // withTrashed: users soft-delete, and a deleted row keeps its place in
        // the unique email index, so creating over one fails on the key rather
        // than bringing the account back.
        $user = User::withTrashed()->firstWhere('email', $email) ?? new User([
            'name' => env('ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => $password,   // the model's 'hashed' cast bcrypts this
        ]);

        // An existing account keeps the password it already has. Re-running
        // this must not overwrite one that was changed after the first deploy.
        $user->forceFill(['is_admin' => true, 'deleted_at' => null])->save();
    }

    public function down(): void
    {
        $email = env('ADMIN_EMAIL');

        if (blank($email)) {
            return;
        }

        $user = User::withTrashed()->firstWhere('email', $email);

        if (! $user) {
            return;
        }

        // Only an unused account is removed. children.created_by_user_id
        // cascades on delete, so dropping an admin who has since added a child
        // would take the child, its memories and its media with it; demoting
        // is the reversal that cannot destroy someone's data.
        $user->ownedChildren()->exists()
            ? $user->forceFill(['is_admin' => false])->save()
            : $user->forceDelete();
    }
};
