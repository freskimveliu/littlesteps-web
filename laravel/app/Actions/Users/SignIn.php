<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * There is no way back from a deleted account: the tombstoned row is not looked
 * through here, and PurgeUserAccount is emptying it regardless. Signing up again
 * on the same address starts an empty account.
 */
class SignIn
{
    /** @return array{user: User, token: string} */
    public function handle(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        // One message for every way of being wrong, so it cannot be used to find
        // out which addresses have accounts.
        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match our records.',
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
        ];
    }
}
