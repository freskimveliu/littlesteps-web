<?php

declare(strict_types=1);

namespace App\Actions\Progress;

use App\Enums\RewardStatus;
use App\Models\Child;
use App\Models\ChildAchievement;
use App\Models\Achievement;
use App\Support\Progress\Metrics;
use Illuminate\Support\Collection;

/**
 * Awards any badge the child now qualifies for.
 *
 * A badge row is written once and never revoked, so retuning a threshold later
 * cannot take one back, and deleting a memory cannot either. Rules read live
 * entries, so a deleted memory also stops counting toward the next one.
 */
class EvaluateAchievements
{
    public function __construct(
        private readonly Metrics $metrics,
        private readonly AwardXp $awardXp,
    ) {}

    /** @return Collection<int, ChildAchievement> the ones unlocked by this call */
    public function handle(Child $child): Collection
    {
        $metrics = $this->metrics->for($child);
        $held = $child->achievements()->pluck('achievement_id');

        $earned = Achievement::query()
            ->active()
            ->whereNotIn('id', $held)
            ->get()
            ->filter(fn (Achievement $badge) => ($metrics[$badge->metric->value] ?? 0) >= $badge->threshold);

        return $earned->map(function (Achievement $badge) use ($child) {
            $unlocked = $child->achievements()->create([
                'achievement_id' => $badge->id,
                'unlocked_at' => now(),
            ]);

            if ($badge->xp > 0) {
                $this->awardXp->handle($child, $badge->xp);
            }

            // The gift is only reserved here — nothing is generated until the
            // parent claims it, so a lapsed account never costs a generation.
            if ($badge->carriesGift()) {
                $child->rewards()->create([
                    'child_achievement_id' => $unlocked->id,
                    'type' => $badge->reward,
                    'status' => RewardStatus::Unclaimed,
                ]);
            }

            return $unlocked->setRelation('achievement', $badge);
        })->values();
    }
}
