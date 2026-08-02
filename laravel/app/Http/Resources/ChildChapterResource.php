<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildChapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildChapter */
class ChildChapterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $child = $this->child;
        $milestones = $this->whenLoaded('milestones');
        $visible = $this->relationLoaded('milestones') ? $this->milestones->where('is_hidden', false) : collect();
        $recorded = $visible->filter(fn ($milestone) => $milestone->isRecorded());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'monthsFrom' => $this->months_from,
            'xp' => $this->xp,
            'sortOrder' => $this->sort_order,
            'isEditable' => $this->is_editable,
            'isUnlocked' => $child ? $this->isUnlockedFor($child) : true,
            'isCompleted' => $this->isCompleted(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'abilities' => $this->abilities(),
            'milestonesTotal' => $visible->count(),
            'milestonesRecorded' => $recorded->count(),
            'milestones' => ChildMilestoneResource::collection($milestones),
        ];
    }
}
