<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use App\Enums\Icon;
use Illuminate\Foundation\Http\FormRequest;

readonly class ChapterData
{
    use RemembersWhatWasSent;

    /** @param  array<int, string>  $sent */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?Icon $icon = null,
        public ?int $monthsFrom = null,
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            icon: isset($validated['icon']) ? Icon::from($validated['icon']) : null,
            monthsFrom: isset($validated['months_from']) ? (int) $validated['months_from'] : null,
            sent: array_keys($validated),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return $this->only([
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'months_from' => $this->monthsFrom,
        ]);
    }
}
