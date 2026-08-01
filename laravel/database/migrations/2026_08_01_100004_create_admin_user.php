<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const EMAIL = 'freskim.veliu@gmail.com';

    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $user = User::withTrashed()->firstOrNew(['email' => self::EMAIL]);

        $user->name = 'Freskim';
        $user->password = 'littlesteps-admin';
        $user->is_admin = true;
        $user->deleted_at = null;
        $user->save();
    }

    public function down(): void
    {
        User::withTrashed()->where('email', self::EMAIL)->forceDelete();
    }
};
