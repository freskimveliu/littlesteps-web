<?php

declare(strict_types=1);

namespace App\Actions\Milestones;

use App\Models\ChildMilestone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A milestone goes while its chapter is still open — guided or the parent's own,
 * empty or recorded. The map belongs to the parent, and a node that will never
 * happen would otherwise hold its chapter open forever.
 *
 * A recorded one leaves its memory behind: the entry is unhooked and stays in the
 * timeline as a free one rather than going down with the node. The XP it earned
 * stays too — XP only ever goes up, so there is nothing here to hand back.
 */
class DeleteMilestone
{
    public function handle(ChildMilestone $milestone, User $editor): void
    {
        abort_unless(
            $milestone->isDeletable(),
            403,
            'This chapter is finished — its map cannot change.',
        );

        DB::transaction(function () use ($milestone, $editor) {
            $milestone->entry()->update([
                'child_milestone_id' => null,
                'updated_by_user_id' => $editor->id,
            ]);

            $milestone->delete();
        });
    }
}
