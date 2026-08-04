<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\KnowsWhoIsAsking;
use App\Models\ChildMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildMilestone */
class ChildMilestoneResource extends JsonResource
{
    use KnowsWhoIsAsking;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $child = $this->child;

        return [
            'id' => $this->id,
            'chapterId' => $this->child_chapter_id,
            'name' => $this->name,
            'icon' => $this->icon?->value ?? $this->whenLoaded('category', fn () => $this->category?->icon->value),
            'monthsFrom' => $this->months_from,
            'isDated' => $this->is_dated,
            'xp' => $this->xp,
            'sortOrder' => $this->sort_order,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'isEditable' => $this->is_editable,
            'isLocked' => $child ? $this->isLockedFor($child) : false,
            'isRecorded' => $this->isRecorded(),
            'abilities' => $this->abilities($this->mayWrite($request)),
            'properties' => ChildMilestonePropertyResource::collection($this->whenLoaded('properties')),
            'entry' => new ChildEntryResource($this->whenLoaded('entry')),
        ];
    }
}
