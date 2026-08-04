<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use App\Enums\Icon;
use App\Enums\PropertyKey;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One shape for both doors. Adding a milestone always names a chapter; moving
 * one only sometimes does, which is what `sent('child_chapter_id')` is for.
 */
readonly class MilestoneData
{
    use RemembersWhatWasSent;

    /**
     * @param  array<int, array{key: PropertyKey, name: ?string}>  $properties
     * @param  array<int, string>  $sent
     */
    public function __construct(
        public ?int $childChapterId = null,
        public ?string $name = null,
        public ?Icon $icon = null,
        public ?int $categoryId = null,
        public ?int $monthsFrom = null,
        public array $properties = [],
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        $properties = collect($validated['properties'] ?? [])
            ->map(fn (array $row) => [
                'key' => PropertyKey::from($row['key']),
                'name' => $row['name'] ?? null,
            ])
            ->all();

        return new self(
            childChapterId: isset($validated['child_chapter_id']) ? (int) $validated['child_chapter_id'] : null,
            name: $validated['name'] ?? null,
            icon: isset($validated['icon']) ? Icon::from($validated['icon']) : null,
            categoryId: ($validated['category_id'] ?? null) ? (int) $validated['category_id'] : null,
            monthsFrom: isset($validated['months_from']) ? (int) $validated['months_from'] : null,
            properties: $properties,
            sent: array_keys($validated),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return $this->only([
            'child_chapter_id' => $this->childChapterId,
            'name' => $this->name,
            'icon' => $this->icon,
            'category_id' => $this->categoryId,
            'months_from' => $this->monthsFrom,
        ]);
    }
}
