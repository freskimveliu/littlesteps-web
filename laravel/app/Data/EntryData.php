<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Mood;
use App\Enums\PropertyKey;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class EntryData
{
    /**
     * @param  array<int, array{key: PropertyKey, name: ?string, value: ?string}>  $properties
     * @param  array<int, UploadedFile>  $media
     */
    public function __construct(
        public ?int $childMilestoneId,
        public ?string $description,
        public string $date,
        public ?Mood $mood,
        public array $properties,
        public array $media = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $properties = collect($request->array('properties'))
            ->map(fn (array $row) => [
                'key' => PropertyKey::from($row['key']),
                'name' => $row['name'] ?? null,
                'value' => isset($row['value']) ? (string) $row['value'] : null,
            ])
            ->all();

        return new self(
            childMilestoneId: $request->integer('child_milestone_id') ?: null,
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            date: $request->date('date')?->toDateString() ?? now()->toDateString(),
            mood: $request->filled('mood') ? Mood::from($request->string('mood')->toString()) : null,
            properties: $properties,
            media: array_values($request->file('media') ?? []),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return [
            'child_milestone_id' => $this->childMilestoneId,
            'description' => $this->description,
            'date' => $this->date,
            'mood' => $this->mood,
        ];
    }

    public function isFree(): bool
    {
        return $this->childMilestoneId === null;
    }
}
