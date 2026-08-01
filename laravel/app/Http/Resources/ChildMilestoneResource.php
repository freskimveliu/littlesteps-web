<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildMilestone */
class ChildMilestoneResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $child = $this->child;

        return [
            'id' => $this->id,
            'milestoneId' => $this->child_chapter_id,
            'name' => $this->name,
            'icon' => $this->icon?->value ?? $this->whenLoaded('category', fn () => $this->category?->icon->value),
            'monthsFrom' => $this->months_from,
            'xp' => $this->xp,
            'sortOrder' => $this->sort_order,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'isEditable' => $this->is_editable,
            'isHidden' => $this->is_hidden,
            'isLocked' => $child ? $this->isLockedFor($child) : false,
            'isRecorded' => $this->isRecorded(),
            'isDeletable' => $this->isDeletable(),
            'properties' => ChildMilestonePropertyResource::collection($this->whenLoaded('properties')),
            'entry' => new ChildEntryResource($this->whenLoaded('entry')),
        ];
    }
}
