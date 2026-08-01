<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildEntryProperty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildEntryProperty */
class ChildEntryPropertyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name ?? ucfirst($this->key->value),
            'unit' => $this->key->unit(),
            'value' => $this->value,
        ];
    }
}
