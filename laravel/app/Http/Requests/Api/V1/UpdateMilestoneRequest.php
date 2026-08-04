<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Icon;
use App\Enums\TimeUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMilestoneRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:80'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'happens_after' => ['nullable', 'integer', 'min:0', 'max:6570'],
            'happens_unit' => ['nullable', Rule::enum(TimeUnit::class)],
            'child_chapter_id' => ['sometimes', 'integer'],
        ];
    }
}
