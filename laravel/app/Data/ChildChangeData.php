<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * An edit to a child. ChildData describes the one being started, and carries the
 * birthday — see UpdateChildRequest for why that one never comes back.
 */
readonly class ChildChangeData
{
    use RemembersWhatWasSent;

    /** @param  array<int, string>  $sent */
    public function __construct(
        public ?string $name = null,
        public ?Gender $gender = null,
        public ?UploadedFile $photo = null,
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            gender: isset($validated['gender']) ? Gender::from($validated['gender']) : null,
            photo: $request->file('photo'),
            sent: array_keys($validated),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return $this->only([
            'name' => $this->name,
            'gender' => $this->gender,
        ]);
    }
}
