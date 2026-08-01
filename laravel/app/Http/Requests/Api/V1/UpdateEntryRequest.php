<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Mood;
use App\Enums\PropertyKey;
use App\Models\ChildEntry;
use App\Support\Limits;
use Illuminate\Contracts\Validation\Validator;
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
            'mood' => ['sometimes', 'required', Rule::enum(Mood::class)],
            'media' => ['array'],
            'media.*' => ['file', 'mimetypes:'.implode(',', ChildEntry::ACCEPTS), 'max:20480'],
            'properties' => ['array'],
            'properties.*.key' => ['required', Rule::enum(PropertyKey::class)],
            'properties.*.name' => ['nullable', 'string', 'max:60'],
            'properties.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A patch is partial, so the rules above can only judge what was sent. What matters
     * is the entry the patch leaves behind — it still has to hold a mood and either
     * words or an attachment.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $entry = $this->route('entry');

            if (! $entry instanceof ChildEntry) {
                return;
            }

            $added = count($this->file('media') ?? []);

            $description = $this->has('description')
                ? trim((string) $this->input('description'))
                : (string) $entry->description;

            if ($description === '' && $entry->mediaCount() + $added === 0) {
                $validator->errors()->add('description', 'A memory needs a few words or something to show for it.');
            }

            $allowed = app(Limits::class)->maxMediaPerEntry();

            if ($entry->mediaCount() + $added > $allowed) {
                $validator->errors()->add('media', "A memory holds up to {$allowed} attachments.");
            }

            $mood = $this->has('mood') ? $this->input('mood') : $entry->mood;

            if (blank($mood)) {
                $validator->errors()->add('mood', 'Tell us how this memory feels.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mood.required' => 'Tell us how this memory feels.',
        ];
    }
}
