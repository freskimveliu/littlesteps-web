<?php

declare(strict_types=1);

namespace App\Actions\Progress;

use App\Enums\RewardStatus;
use App\Models\Child;
use App\Models\ChildTrophy;
use App\Models\Trophy;
use App\Support\Progress\Metrics;
use Illuminate\Support\Collection;

/**
 * Awards any trophy the child now qualifies for.
 *
 * A trophy row is written once and never revoked, so retuning a threshold later
 * cannot take one back, and deleting a memory cannot either. Rules read live
 * entries, so a deleted memory also stops counting toward the next one.
 */
class EvaluateTrophies
{
    public function __construct(
        private readonly Metrics $metrics,
        private readonly AwardXp $awardXp,
    ) {}

    /** @return Collection<int, ChildTrophy> the ones unlocked by this call */
    public function handle(Child $child): Collection
    {
        $metrics = $this->metrics->for($child);
        $held = $child->trophies()->pluck('trophy_id');

        $earned = Trophy::query()
            ->active()
            ->whereNotIn('id', $held)
            ->get()
            ->filter(fn (Trophy $trophy) => ($metrics[$trophy->metric->value] ?? 0) >= $trophy->threshold);

        return $earned->map(function (Trophy $trophy) use ($child) {
            // Copied, not joined — this is what the child earned, whatever the
            // catalogue says about it afterwards.
            $unlocked = $child->trophies()->create([
                'trophy_id' => $trophy->id,
                'name' => $trophy->name,
                'description' => $trophy->description,
                'icon' => $trophy->icon,
                'metric' => $trophy->metric,
                'threshold' => $trophy->threshold,
                'xp' => $trophy->xp,
                'reward' => $trophy->reward,
                'sort_order' => $trophy->sort_order,
                'unlocked_at' => now(),
            ]);

            if ($trophy->xp > 0) {
                $this->awardXp->handle($child, $trophy->xp);
            }

            // The gift is only reserved here — nothing is generated until the
            // parent claims it, so a lapsed account never costs a generation.
            if ($unlocked->carriesGift()) {
                $child->rewards()->create([
                    'child_trophy_id' => $unlocked->id,
                    'type' => $unlocked->reward,
                    'status' => RewardStatus::Unclaimed,
                ]);
            }

            return $unlocked;
        })->values();
    }
}
