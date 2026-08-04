<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'child_id', 'child_chapter_id', 'milestone_id', 'category_id',
    'name', 'icon', 'months_from', 'typical_days', 'is_dated', 'xp', 'sort_order',
    'is_editable', 'created_by_user_id', 'updated_by_user_id',
])]
class ChildMilestone extends Model
{
    /** A milestone a parent writes is never a date — only the catalogue sets this. */
    protected $attributes = ['is_dated' => false];

    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_dated' => 'boolean',
            'is_editable' => 'boolean',
        ];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<ChildChapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ChildChapter::class, 'child_chapter_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasOne<ChildEntry, $this> */
    public function entry(): HasOne
    {
        return $this->hasOne(ChildEntry::class);
    }

    /** @return HasMany<ChildMilestoneProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(ChildMilestoneProperty::class);
    }

    public function isRecorded(): bool
    {
        return $this->relationLoaded('entry')
            ? $this->entry !== null
            : $this->entry()->exists();
    }

    public function isLockedFor(Child $child): bool
    {
        return $this->months_from !== null && $child->ageInMonths() < $this->months_from;
    }

    public function isOutOfReachFor(Child $child): bool
    {
        if ($this->isLockedFor($child)) {
            return true;
        }

        $chapter = $this->relationLoaded('chapter') ? $this->chapter : $this->chapter()->first();

        return $chapter !== null && ! $chapter->isUnlockedFor($child);
    }

    /**
     * A milestone goes whenever its chapter is still open — empty or recorded,
     * guided or the parent's own. Not every child has siblings or a christening,
     * and a map cannot be finished while it carries a node that will never happen.
     *
     * Deleting a recorded one keeps the memory: the entry is unhooked and stays in
     * the timeline as a free one. The XP it earned stays too — XP only ever goes
     * up, so there is nothing here to hand back.
     */
    public function isDeletable(): bool
    {
        return ! $this->isSealed();
    }

    /**
     * This milestone *is* a date rather than something that happens near one.
     * "Month 5" is the fifth month and "Fourth Birthday" is one day; both are fixed
     * points on the calendar, so neither may change chapter, swap with a neighbour
     * or have its age edited. A first haircut is not dated — it happens when it
     * happens, and belongs wherever the parent files the memory.
     *
     * Coming from the catalogue is not the test: most guided milestones are firsts.
     */
    public function isDated(): bool
    {
        return $this->is_dated;
    }

    /**
     * The day a dated milestone falls on — the birthday plus the months it names.
     * A memory filed against it takes this and nothing else, because the parent is
     * not choosing a date, they are filling in a day the calendar already fixed.
     *
     * Null for everything else: a first has no day until it happens.
     */
    /**
     * Where this milestone sits is not the parent's to change.
     *
     * Two reasons, and they land on the same answer: one that names a date is that
     * date, and one the child has not reached is a stretch of map that has not
     * happened yet. Rearranging either arranges a preview.
     */
    public function isPinned(?Child $child = null): bool
    {
        return $this->isDated() || ($child !== null && $this->isLockedFor($child));
    }

    public function dateFor(Child $child): ?CarbonImmutable
    {
        if (! $this->isDated() || $this->months_from === null) {
            return null;
        }

        return CarbonImmutable::parse($child->birthday)->addMonths($this->months_from);
    }

    /**
     * A milestone in a finished chapter is part of a keepsake somebody chose to
     * close, so its shape and its name are fixed with it.
     */
    public function isSealed(): bool
    {
        return $this->relationLoaded('chapter')
            ? (bool) $this->chapter?->isCompleted()
            : $this->chapter()->whereNotNull('completed_at')->exists();
    }

    /**
     * Everything a parent may do to this milestone, decided here so the app never
     * has to know the rules — it renders the buttons this says it can.
     *
     * `reorder` is permission, not position: whether a neighbour exists to swap
     * with is a question about the list the app is already holding.
     *
     * The map is the parent's, guided or not — a name that does not match the child
     * is worse than no name, and a guided node that will never happen can still go.
     * is_editable records where the row came from; it does not decide what may be
     * done to it. is_dated does, and only over position: a milestone that names a
     * date cannot be carried to a chapter that is not that date.
     *
     * Nor does a locked one move. It sits in a stretch of the map the child has not
     * reached, and rearranging what has not happened yet is arranging a preview —
     * the same reason `StoreMilestoneController` will not add to a locked chapter.
     *
     * A seal leaves `record` alone: a chapter only finishes once every milestone
     * in it holds a memory, and a milestone memory is permanent, so nothing
     * inside a finished chapter is ever waiting to be recorded anyway.
     *
     * Who is asking comes first: a member who may only look is handed every one of
     * these false, the same as their chapter.
     *
     * @return array<string, bool>
     */
    public function abilities(bool $mayWrite): array
    {
        $child = $this->child;
        $locked = $child ? $this->isLockedFor($child) : false;
        $outOfReach = $child ? $this->isOutOfReachFor($child) : false;
        $recorded = $this->isRecorded();
        $sealed = $this->isSealed();
        $pinned = $this->isPinned($child);

        return [
            'rename' => $mayWrite && ! $sealed,
            'move' => $mayWrite && ! $sealed && ! $pinned,
            'reorder' => $mayWrite && ! $sealed && ! $pinned,
            'retime' => $mayWrite && ! $sealed && ! $this->isDated() && ! $locked && ! $recorded,
            'setDate' => $mayWrite && ! $this->isDated(),
            'delete' => $mayWrite && $this->isDeletable(),
            'record' => $mayWrite && ! $recorded && ! $outOfReach,
        ];
    }
}
