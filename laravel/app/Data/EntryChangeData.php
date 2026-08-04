<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use App\Enums\Mood;
use App\Enums\PropertyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * An edit to a memory. Its sibling EntryData describes a whole new one; this one
 * describes a correction, so every field is optional and `properties` being null
 * means "left alone" rather than "cleared".
 */
readonly class EntryChangeData
{
    use RemembersWhatWasSent;

    /**
     * @param  array<int, array{key: PropertyKey, name: ?string, value: ?string}>|null  $properties
     * @param  array<int, UploadedFile>  $media
     * @param  array<int, string>  $sent
     */
    public function __construct(
        public ?string $description = null,
        public ?string $date = null,
        public ?Mood $mood = null,
        public ?array $properties = null,
        public array $media = [],
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        $properties = isset($validated['properties'])
            ? collect($validated['properties'])
                ->map(fn (array $row) => [
                    'key' => PropertyKey::from($row['key']),
                    'name' => $row['name'] ?? null,
                    'value' => isset($row['value']) ? (string) $row['value'] : null,
                ])
                ->all()
            : null;

        return new self(
            description: $validated['description'] ?? null,
            date: isset($validated['date']) ? $request->date('date')->toDateString() : null,
            mood: isset($validated['mood']) ? Mood::from($validated['mood']) : null,
            properties: $properties,
            media: array_values($request->file('media') ?? []),
            sent: array_keys($validated),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return $this->only([
            'description' => $this->description,
            'date' => $this->date,
            'mood' => $this->mood,
        ]);
    }
}
