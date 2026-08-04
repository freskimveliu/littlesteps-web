<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Child;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Deleting a child takes the whole album with it — every chapter, every
 * milestone, every memory and every photo, and none of it comes back.
 *
 * A chapter holding a single memory already refuses to go quietly; this is the
 * same rule at the top of the tree. Once anything has been written down, the
 * name has to be typed back, so no mis-tap and no retried request can do it.
 * An empty adventure has nothing to lose and goes on asking.
 */
class DestroyChildRequest extends FormRequest
{
    /**
     * Answered here as well as in the controller, so a stranger cannot learn
     * from the error message whether somebody else's child holds memories.
     */
    public function authorize(): bool
    {
        $child = $this->route('child');

        return $child instanceof Child && $this->user()->can('delete', $child) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm' => ['sometimes', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Child $child */
            $child = $this->route('child');

            if (! $child->entries()->exists()) {
                return;
            }

            if (Str::lower(trim((string) $this->input('confirm'))) !== Str::lower($child->name)) {
                $validator->errors()->add(
                    'confirm',
                    "This adventure holds memories that cannot be brought back. Type “{$child->name}” to delete it.",
                );
            }
        });
    }
}
