<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Mood;
use App\Enums\PropertyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['sometimes', 'date', 'before_or_equal:today'],
            'mood' => ['nullable', Rule::enum(Mood::class)],
            'properties' => ['array'],
            'properties.*.key' => ['required', Rule::enum(PropertyKey::class)],
            'properties.*.name' => ['nullable', 'string', 'max:60'],
            'properties.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
