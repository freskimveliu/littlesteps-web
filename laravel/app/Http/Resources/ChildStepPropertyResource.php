<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildStepProperty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildStepProperty */
class ChildStepPropertyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->label(),
            'unit' => $this->key->unit(),
            'isChartable' => $this->key->isChartable(),
            'sortOrder' => $this->sort_order,
        ];
    }
}
