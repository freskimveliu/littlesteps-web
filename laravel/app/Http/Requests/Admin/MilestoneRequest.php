<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Icon;
use App\Enums\PropertyKey;
use App\Enums\TimeUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MilestoneRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {

        return [
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'happens_after' => ['required', 'integer', 'min:0', 'max:6570'],
            'happens_unit' => ['required', Rule::enum(TimeUnit::class)],
            'is_date_editable' => ['boolean'],
            'xp' => ['required', 'integer', 'min:0', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'properties' => ['array', 'max:10'],
            'properties.*.key' => ['required', Rule::enum(PropertyKey::class)],
            'properties.*.name' => ['nullable', 'string', 'max:60', 'required_if:properties.*.key,custom'],
        ];
    }
}
