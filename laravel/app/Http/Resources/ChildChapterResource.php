<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildChapter;
use App\Support\Limits;
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
        $limits = app(Limits::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'monthsFrom' => $this->months_from,
            'xp' => $this->xp,
            'sortOrder' => $this->sort_order,
            'isEditable' => $this->is_editable,
            'isDeletable' => $this->isDeletable(),
            'isUnlocked' => $child ? $this->isUnlockedFor($child) : true,
            'isCompleted' => $this->isCompleted(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'isCompletable' => $limits->canCompleteMilestone($this->resource),
            'canAddMilestone' => $limits->canAddCustomStep($this->resource),
            'stepsTotal' => $visible->count(),
            'stepsRecorded' => $recorded->count(),
            'milestones' => ChildMilestoneResource::collection($milestones),
        ];
    }
}
