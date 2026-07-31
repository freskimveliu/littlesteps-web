<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Actions\Progress\AwardXp;
use App\Actions\Progress\EvaluateAchievements;
use App\Data\EntryData;
use App\Models\Child;
use App\Models\ChildAchievement;
use App\Models\ChildEntry;
use App\Models\ChildStep;
use App\Models\User;
use App\Support\Limits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordEntry
{
    public function __construct(
        private readonly Limits $limits,
        private readonly AwardXp $awardXp,
        private readonly EvaluateAchievements $achievements,
        private readonly UpdateStreak $streak,
    ) {}

    /** @return array{entry: ChildEntry, xp: int, unlocked: Collection<int, ChildAchievement>} */
    public function handle(Child $child, User $user, EntryData $data): array
    {
        $step = $data->isFree() ? null : $this->step($child, $data->childStepId);

        $this->guardLimits($child, $user, $data);

        $entry = DB::transaction(function () use ($child, $user, $data) {
            $entry = $child->entries()->create([
                ...$data->toAttributes(),
                'created_by_user_id' => $user->id,
            ]);

            foreach ($data->properties as $i => $property) {
                $entry->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }

            return $entry;
        });

        $xp = $step?->xp ?? $this->limits->freeEntryXp();
        $this->awardXp->handle($child, $xp);
        $this->streak->handle($user, $entry);

        return [
            'entry' => $entry->load(['properties', 'step']),
            'xp' => $xp,
            'unlocked' => $this->achievements->handle($child->refresh()),
        ];
    }

    private function step(Child $child, int $stepId): ChildStep
    {
        $step = $child->steps()->with('entry')->find($stepId);

        if (! $step) {
            throw ValidationException::withMessages([
                'child_step_id' => 'That step does not belong to this child.',
            ]);
        }

        if ($step->isRecorded()) {
            throw ValidationException::withMessages([
                'child_step_id' => 'This step already has a memory. Edit it instead.',
            ]);
        }

        if ($step->isLockedFor($child)) {
            throw ValidationException::withMessages([
                'child_step_id' => 'This step is not open yet.',
            ]);
        }

        return $step;
    }

    private function guardLimits(Child $child, User $user, EntryData $data): void
    {
        $left = $data->isFree()
            ? $this->limits->freeEntriesLeft($child, $user)
            : $this->limits->stepEntriesLeft($child, $user);

        if ($left > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'date' => $data->isFree()
                ? 'One memory a day keeps it precious. Come back tomorrow for the next one.'
                : 'That is enough steps for today — the rest will keep until tomorrow.',
        ]);
    }
}
