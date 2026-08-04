<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The whole profile in one call, photo included. A file cannot ride a PATCH from
 * every client, so the app posts this with `_method=PATCH` when carrying one.
 */
class UpdateProfileRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'current_password' => ['sometimes', 'string'],
            'language' => ['sometimes', 'string', 'in:en'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'photo' => ['sometimes', 'image', 'max:20480'],
            'settings' => ['sometimes', 'array'],
            'settings.*' => ['boolean'],
        ];
    }

    /**
     * Moving the address or the password means proving you already hold the
     * account — a phone left unlocked on a table should not be enough to take it
     * over. An account that has never set a password has nothing to prove yet.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            $movingEmail = $this->has('email') && $this->input('email') !== $user->email;

            if ($user->password === null || (! $movingEmail && ! $this->has('password'))) {
                return;
            }

            if (! Hash::check((string) $this->input('current_password'), $user->password)) {
                $validator->errors()->add('current_password', 'That is not your current password.');
            }
        });
    }
}
