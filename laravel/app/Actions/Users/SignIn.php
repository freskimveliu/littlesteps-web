<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Also the way back from a closed account: this looks through the soft delete,
 * so a parent who changed their mind signs in as normal and finds everything
 * where they left it. Nothing expires, so there is no wrong day to come back on.
 */
class SignIn
{
    /** @return array{user: User, token: string, reopened: bool} */
    public function handle(string $email, string $password): array
    {
        $user = User::withTrashed()->where('email', $email)->first();

        // One message for every way of being wrong, so it cannot be used to find
        // out which addresses have accounts.
        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match our records.',
            ]);
        }

        $reopened = $user->trashed();

        if ($reopened) {
            $user->restore();
        }

        return [
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
            'reopened' => $reopened,
        ];
    }
}
