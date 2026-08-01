<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Gender;
use App\Enums\Relation;
use Illuminate\Http\Request;

readonly class ChildData
{
    public function __construct(
        public string $name,
        public string $birthday,
        public Gender $gender,
        public Relation $relation,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            birthday: $request->string('birthday')->toString(),
            gender: Gender::from($request->string('gender')->toString()),
            relation: Relation::from($request->string('relation')->toString()),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
        ];
    }
}
