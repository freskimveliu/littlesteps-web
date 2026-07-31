<?php

declare(strict_types=1);

namespace App\Actions\Milestones;

use App\Actions\Progress\AwardXp;
use App\Actions\Progress\EvaluateAchievements;
use App\Models\ChildAchievement;
use App\Models\ChildMilestone;
use App\Models\User;
use App\Support\Limits;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Finishing a chapter is the parent's own act, not a total that quietly ticks
 * over — which is what makes the chapters badge a moment somebody chose.
 */
class CompleteMilestone
{
    public function __construct(
        private readonly Limits $limits,
        private readonly AwardXp $awardXp,
        private readonly EvaluateAchievements $achievements,
    ) {}

    /** @return array{milestone: ChildMilestone, xp: int, unlocked: Collection<int, ChildAchievement>} */
    public function handle(ChildMilestone $milestone, User $user): array
    {
        if (! $this->limits->canCompleteMilestone($milestone)) {
            throw ValidationException::withMessages([
                'milestone' => 'Every step in this chapter needs a memory first.',
            ]);
        }

        $milestone->forceFill([
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
        ])->save();

        $child = $milestone->child;
        $this->awardXp->handle($child, $milestone->xp);

        return [
            'milestone' => $milestone,
            'xp' => $milestone->xp,
            'unlocked' => $this->achievements->handle($child->refresh()),
        ];
    }
}
