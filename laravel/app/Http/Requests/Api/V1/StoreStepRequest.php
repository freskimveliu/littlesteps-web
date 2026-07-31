<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Icon;
use App\Enums\PropertyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStepRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'child_milestone_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
            'properties' => ['array', 'max:10'],
            'properties.*.key' => ['required', Rule::enum(PropertyKey::class)],
            'properties.*.name' => ['nullable', 'string', 'max:60', 'required_if:properties.*.key,custom'],
        ];
    }
}
