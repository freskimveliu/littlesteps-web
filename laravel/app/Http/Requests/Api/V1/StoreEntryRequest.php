<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Mood;
use App\Enums\PropertyKey;
use App\Http\Requests\Api\V1\Concerns\BoundsTheDate;
use App\Models\ChildEntry;
use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryRequest extends FormRequest
{
    use BoundsTheDate;

    /** @return array<string, mixed> */
    public function rules(Limits $limits): array
    {
        return [
            'child_milestone_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'date' => ['required', 'date', 'before_or_equal:today', ...$this->notBeforeBirth()],
            'mood' => ['required', Rule::enum(Mood::class)],
            'media' => ['array', 'max:'.$limits->maxMediaPerEntry()],
            'media.*' => ['file', 'mimetypes:'.implode(',', ChildEntry::ACCEPTS), 'max:20480'],
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
            'description.required_without' => 'A memory needs a few words or something to show for it.',
            'mood.required' => 'Tell us how this memory feels.',
            'media.max' => 'A memory holds up to :max attachments.',
            'media.*.max' => 'Each photo has to be under 20 MB.',
            'date.after_or_equal' => 'A memory cannot be older than the child.',
        ];
    }
}
