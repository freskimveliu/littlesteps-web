<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The birthday is set once, when the child is created, and is deliberately not
 * here.
 *
 * It is the origin the whole map is measured from: every chapter and milestone
 * unlocks against it, a dated milestone *is* the birthday plus its months, and a
 * memory filed against one of those was given that day rather than choosing it.
 * Move the birthday and those memories keep days that no longer mean anything —
 * and the rule that fixed them refuses to let the parent correct them by hand.
 *
 * Carrying them along is possible but changes what is already written down, which
 * is the one thing this app does not do. A birthday typed in wrongly is fixed by
 * starting the adventure again.
 */
class UpdateChildRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60'],
            'gender' => ['sometimes', Rule::enum(Gender::class)],
            'photo' => ['sometimes', 'image', 'max:20480'],
        ];
    }
}
