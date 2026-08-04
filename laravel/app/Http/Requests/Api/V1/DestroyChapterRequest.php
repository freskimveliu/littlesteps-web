<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DestroyChapterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'move_milestones_to' => ['nullable', 'integer'],
        ];
    }

    /** Which chapter the milestones should be carried to, if any. */
    public function moveTo(): ?int
    {
        return $this->integer('move_milestones_to') ?: null;
    }
}
