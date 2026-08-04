<?php

declare(strict_types=1);

namespace App\Actions\Milestones;

use App\Data\MilestoneData;
use App\Models\Child;
use App\Models\ChildMilestone;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateMilestone
{
    public function handle(ChildMilestone $milestone, Child $child, User $editor, MilestoneData $data): ChildMilestone
    {
        // A dated milestone is the date it names: "Month 5" filed under a summer
        // holiday is not a month five. Rename it, delete it, but it stays put.
        if (! $milestone->isDateEditable()) {
            if ($data->sent('child_chapter_id')) {
                throw ValidationException::withMessages([
                    'child_chapter_id' => 'This milestone is a date and cannot be moved to another chapter.',
                ]);
            }

            if ($data->sent('happens_after') || $data->sent('happens_unit')) {
                throw ValidationException::withMessages([
                    'happens_after' => 'This milestone is a date and cannot be moved in time.',
                ]);
            }
        }

        // Nor does a locked one move. `abilities.move` has said false for these all
        // along — this is the door that was still letting them through.
        if ($data->sent('child_chapter_id') && $milestone->isLockedFor($child)) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'This milestone has not opened yet, so it stays where it is.',
            ]);
        }

        if ($data->sent('happens_after') || $data->sent('happens_unit')) {
            if ($milestone->isLockedFor($child)) {
                throw ValidationException::withMessages([
                    'happens_after' => 'This milestone has not opened yet, so its age cannot be changed.',
                ]);
            }

            if ($milestone->isRecorded()) {
                throw ValidationException::withMessages([
                    'happens_after' => 'This milestone already holds a memory, so its age is part of the story now.',
                ]);
            }
        }

        if ($data->childChapterId !== null) {
            $chapter = $child->chapters()->findOrFail($data->childChapterId);

            // A chapter the child has not grown into yet is a preview, not a
            // shelf — dropping a milestone in there would hide it from them.
            if (! $chapter->isUnlockedFor($child)) {
                throw ValidationException::withMessages([
                    'child_chapter_id' => 'That chapter has not opened yet.',
                ]);
            }

            // A finished chapter counted its milestones when its gift was given;
            // one arriving afterwards would leave it complete with an empty node.
            if ($chapter->isCompleted()) {
                throw ValidationException::withMessages([
                    'child_chapter_id' => 'That chapter is finished and cannot take new milestones.',
                ]);
            }
        }

        $milestone->update([...$data->toAttributes(), 'updated_by_user_id' => $editor->id]);

        return $milestone->fresh();
    }
}
