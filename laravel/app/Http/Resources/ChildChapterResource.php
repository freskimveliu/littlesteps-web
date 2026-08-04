<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\KnowsWhoIsAsking;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildChapter */
class ChildChapterResource extends JsonResource
{
    use KnowsWhoIsAsking;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $child = $this->child;
        $milestones = $this->whenLoaded('milestones');
        $all = $this->relationLoaded('milestones') ? $this->milestones : collect();
        $recorded = $all->filter(fn (ChildMilestone $milestone) => $milestone->isRecorded());

        // Each milestone asks its chapter whether it is sealed, and the chapter
        // is right here — handing it over saves a query per row. Stripped of its
        // own relations, or chapter and milestone would point at each other and
        // anything that later walked the graph would not come back.
        if ($this->relationLoaded('milestones')) {
            $sealer = $this->resource->withoutRelations();
            $this->milestones->each(fn (ChildMilestone $milestone) => $milestone->setRelation('chapter', $sealer));
        }

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
            'abilities' => $this->abilities($this->mayWrite($request)),
            'milestonesTotal' => $all->count(),
            'milestonesRecorded' => $recorded->count(),
            'milestones' => ChildMilestoneResource::collection($milestones),
        ];
    }
}
