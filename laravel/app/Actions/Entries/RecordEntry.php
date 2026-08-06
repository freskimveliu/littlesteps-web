<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Actions\Progress\AwardXp;
use App\Actions\Progress\EvaluateTrophies;
use App\Data\EntryData;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\ChildMilestone;
use App\Models\ChildTrophy;
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
        private readonly EvaluateTrophies $trophies,
        private readonly UpdateStreak $streak,
    ) {}

    /** @return array{entry: ChildEntry, xp: int, unlocked: Collection<int, ChildTrophy>} */
    public function handle(Child $child, User $user, EntryData $data): array
    {
        $milestone = $data->isFree() ? null : $this->milestone($child, $data->childMilestoneId);

        $this->guardLimits($child, $user, $data);

        // A dated milestone brings its own day — the parent never picked one.
        $fixed = $milestone?->dateFor($child);

        $entry = DB::transaction(function () use ($child, $user, $data, $fixed) {
            $entry = $child->entries()->create([
                ...$data->toAttributes(),
                ...($fixed ? ['date' => $fixed->toDateString()] : []),
                'created_by_user_id' => $user->id,
            ]);

            foreach ($data->properties as $i => $property) {
                $entry->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }

            foreach ($data->media as $file) {
                $entry->attachPhoto($file);
            }

            return $entry;
        });

        $xp = $milestone?->xp ?? $this->limits->freeEntryXp();
        $this->awardXp->handle($child, $xp);
        $this->streak->handle($user, $entry);

        return [
            'entry' => $entry->load(['properties', 'milestone.chapter', 'media', 'creator', 'editor'])->bindMediaOwner(),
            'xp' => $xp,
            'unlocked' => $this->trophies->handle($child->refresh()),
        ];
    }

    private function milestone(Child $child, int $milestoneId): ChildMilestone
    {
        $milestone = $child->milestones()->with(['entry', 'chapter'])->find($milestoneId);

        if (! $milestone) {
            throw ValidationException::withMessages([
                'child_milestone_id' => 'That milestone does not belong to this child.',
            ]);
        }

        if ($milestone->isRecorded()) {
            throw ValidationException::withMessages([
                'child_milestone_id' => 'This milestone already has a memory. Edit it instead.',
            ]);
        }

        if ($milestone->isOutOfReachFor($child)) {
            throw ValidationException::withMessages([
                'child_milestone_id' => 'This milestone is not open yet.',
            ]);
        }

        return $milestone;
    }

    private function guardLimits(Child $child, User $user, EntryData $data): void
    {
        $left = $data->isFree()
            ? $this->limits->freeEntriesLeft($child, $user)
            : $this->limits->milestoneEntriesLeft($child, $user);

        if ($left > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'date' => $data->isFree()
                ? 'One memory a day keeps it precious. Come back tomorrow for the next one.'
                : 'That is enough milestones for today — the rest will keep until tomorrow.',
        ]);
    }
}
