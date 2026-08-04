<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use App\Enums\MemberRole;
use App\Enums\Relation;
use Illuminate\Foundation\Http\FormRequest;

readonly class MemberData
{
    use RemembersWhatWasSent;

    /** @param  array<int, string>  $sent */
    public function __construct(
        public ?string $shareCode = null,
        public ?Relation $relation = null,
        public ?MemberRole $role = null,
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            shareCode: isset($validated['share_code'])
                ? strtoupper(trim((string) $validated['share_code']))
                : null,
            relation: isset($validated['relation']) ? Relation::from($validated['relation']) : null,
            role: isset($validated['role']) ? MemberRole::from($validated['role']) : null,
            sent: array_keys($validated),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return $this->only([
            'relation' => $this->relation,
            'role' => $this->role,
        ]);
    }
}
