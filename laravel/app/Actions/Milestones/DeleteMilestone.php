<?php

declare(strict_types=1);

namespace App\Actions\Milestones;

use App\Models\ChildMilestone;

/**
 * A milestone goes while its chapter is still open — guided or the parent's own,
 * empty or recorded. The map belongs to the parent, and a node that will never
 * happen would otherwise hold its chapter open forever.
 *
 * A recorded one takes its memory with it. The XP it earned stays — XP only ever
 * goes up, so there is nothing here to hand back.
 */
class DeleteMilestone
{
    public function handle(ChildMilestone $milestone): void
    {
        abort_unless(
            $milestone->isDeletable(),
            403,
            'This chapter is finished — its map cannot change.',
        );

        $milestone->delete();
    }
}
