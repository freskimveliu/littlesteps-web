<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Actions\Progress\AwardXp;
use App\Actions\Progress\EvaluateAchievements;
use App\Models\ChildAchievement;
use App\Models\ChildChapter;
use App\Models\User;
use App\Support\Limits;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Finishing a chapter is the parent's own act, not a total that quietly ticks
 * over — which is what makes the chapters badge a moment somebody chose.
 */
class CompleteChapter
{
    public function __construct(
        private readonly Limits $limits,
        private readonly AwardXp $awardXp,
        private readonly EvaluateAchievements $achievements,
    ) {}

    /** @return array{chapter: ChildChapter, xp: int, unlocked: Collection<int, ChildAchievement>} */
    public function handle(ChildChapter $chapter, User $user): array
    {
        if (! $this->limits->canCompleteMilestone($chapter)) {
            throw ValidationException::withMessages([
                'chapter' => 'Every milestone in this chapter needs a memory first.',
            ]);
        }

        $chapter->forceFill([
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
        ])->save();

        $child = $chapter->child;
        $this->awardXp->handle($child, $chapter->xp);

        return [
            'chapter' => $chapter,
            'xp' => $chapter->xp,
            'unlocked' => $this->achievements->handle($child->refresh()),
        ];
    }
}
