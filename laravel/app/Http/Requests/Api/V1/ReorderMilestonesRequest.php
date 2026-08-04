<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** @see ReorderChaptersRequest for why the list is only shape-checked here. */
class ReorderMilestonesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'milestones' => ['required', 'array', 'min:1'],
            'milestones.*' => ['integer'],
        ];
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return $this->array('milestones');
    }
}
