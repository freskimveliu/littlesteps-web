<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * The first way into the console, so a fresh install is not locked out of itself.
 *
 * It creates and never updates. A deploy that ran this again would put the
 * password below back on an account whose owner had since changed it, and would
 * undo a deliberate deletion — so once the row exists, this leaves it alone.
 * Change the password from the console after the first sign-in.
 */
return new class extends Migration
{
    private const EMAIL = 'admin@littlesteps.app';

    private const PASSWORD = 'littlesteps-admin';

    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (User::withTrashed()->where('email', self::EMAIL)->exists()) {
            return;
        }

        $user = User::create([
            'name' => 'Admin',
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        $user->forceFill(['is_admin' => true])->save();
    }

    public function down(): void
    {
        User::withTrashed()->where('email', self::EMAIL)->forceDelete();
    }
};
