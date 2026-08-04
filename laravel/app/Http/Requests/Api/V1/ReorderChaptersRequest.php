<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only. Whether the list is *the* list, and whether it respects what is
 * pinned, is asked in the action once the caller is through the door.
 */
class ReorderChaptersRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'chapters' => ['required', 'array', 'min:1'],
            'chapters.*' => ['integer'],
        ];
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return $this->array('chapters');
    }
}
