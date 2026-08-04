<?php

declare(strict_types=1);

namespace App\Actions\Milestones;

use App\Data\MilestoneData;
use App\Models\Child;
use App\Models\ChildMilestone;
use App\Models\User;
use App\Support\Limits;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A milestone the parent adds to their own map. It is worth the same twenty xp
 * as one from the catalogue — the map belongs to them, and a node they thought
 * of is not worth less than one we did.
 */
class CreateMilestone
{
    private const XP = 20;

    public function __construct(private readonly Limits $limits) {}

    public function handle(Child $child, User $author, MilestoneData $data): ChildMilestone
    {
        $chapter = $child->chapters()->findOrFail($data->childChapterId);

        if (! $chapter->isUnlockedFor($child)) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'That chapter has not opened yet.',
            ]);
        }

        // A finished chapter counted its milestones when its gift was given; one
        // arriving afterwards would leave it complete with an empty node.
        if ($chapter->isCompleted()) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'That chapter is finished and cannot take new milestones.',
            ]);
        }

        if (! $this->limits->canAddCustomMilestone($chapter)) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'This chapter already has as many of your own milestones as it can hold.',
            ]);
        }

        return DB::transaction(function () use ($child, $chapter, $author, $data) {
            $milestone = $child->milestones()->create([
                ...$data->toAttributes(),
                'child_chapter_id' => $chapter->id,
                'months_from' => $data->monthsFrom ?? $chapter->months_from,
                'xp' => self::XP,
                'sort_order' => ($chapter->milestones()->max('sort_order') ?? 0) + 10,
                'is_editable' => true,
                'created_by_user_id' => $author->id,
            ]);

            foreach ($data->properties as $i => $property) {
                $milestone->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }

            return $milestone;
        });
    }
}
