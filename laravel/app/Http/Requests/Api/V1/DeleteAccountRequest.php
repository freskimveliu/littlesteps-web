<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Closing an account takes every child it started, every memory under them and
 * every photo, and none of it comes back.
 *
 * Same rule as deleting a child, one level up: once it cannot be undone, the
 * name has to be typed back, so no mis-tap and no retried request can do it.
 */
class DeleteAccountRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phrase = $this->user()->deletionPhrase();

            if (Str::lower(trim((string) $this->input('confirm'))) === Str::lower($phrase)) {
                return;
            }

            $validator->errors()->add(
                'confirm',
                "This takes every child, every memory and every photo with it, and none of it comes back. Type “{$phrase}” to delete your account.",
            );
        });
    }
}
