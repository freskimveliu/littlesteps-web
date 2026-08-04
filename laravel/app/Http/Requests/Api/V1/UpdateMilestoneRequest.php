<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Icon;
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
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
            'child_chapter_id' => ['sometimes', 'integer'],
        ];
    }
}
