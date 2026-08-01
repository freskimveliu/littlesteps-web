<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Mood;
use App\Enums\PropertyKey;
use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(Limits $limits): array
    {
        return [
            'child_milestone_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:5000', 'required_without:photos'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'mood' => ['required', Rule::enum(Mood::class)],
            'photos' => ['array', 'max:'.$limits->maxMediaPerEntry()],
            'photos.*' => ['image', 'max:20480'],
            'properties' => ['array'],
            'properties.*.key' => ['required', Rule::enum(PropertyKey::class)],
            'properties.*.name' => ['nullable', 'string', 'max:60', 'required_if:properties.*.key,custom'],
            'properties.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'description.required_without' => 'A memory needs a few words or a photo.',
            'mood.required' => 'Tell us how this memory feels.',
            'photos.max' => 'A memory can hold at most :max photos.',
        ];
    }
}
