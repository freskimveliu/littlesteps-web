<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use App\Enums\TimeUnit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'child_id', 'child_chapter_id', 'milestone_id', 'category_id',
    'name', 'icon', 'happens_after', 'happens_unit', 'is_date_editable',
    'xp', 'sort_order', 'is_editable', 'created_by_user_id', 'updated_by_user_id',
])]
class ChildMilestone extends Model
{
    /** A milestone a parent writes is never a date — only the catalogue sets this. */
    protected $attributes = ['is_date_editable' => true];

    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'happens_unit' => TimeUnit::class,
            'is_date_editable' => 'boolean',
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

    /**
     * A milestone the child has not reached yet.
     *
     * The two halves of the map answer this differently, and the difference is
     * whether `happens_after` is a promise or a guess. A milestone that owns its
     * date keeps until that date arrives — nobody records a fifth month in the
     * fourth. A first only *estimates* when it happens, so it cannot gate on that:
     * a baby who smiles three weeks early is still a baby who smiled, and the
     * parent must be able to write it down. Those open with their chapter.
     */
    public function isLockedFor(Child $child): bool
    {
        $chapter = $this->chapterFor();

        if ($chapter !== null && ! $chapter->isUnlockedFor($child)) {
            return true;
        }

        return ! $this->isDateEditable() && $this->happensFor($child)?->startOfDay()->isFuture() === true;
    }

    public function isOutOfReachFor(Child $child): bool
    {
        return $this->isLockedFor($child);
    }

    private function chapterFor(): ?ChildChapter
    {
        return $this->relationLoaded('chapter') ? $this->chapter : $this->chapter()->first();
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
     * Whether the parent chooses this milestone's day, or the calendar already has.
     *
     * False for the ones that *are* a date — "Month 5" is the fifth month and
     * "Fourth Birthday" is one day. Both are fixed points, so neither may change
     * chapter, swap with a neighbour or have its age edited. True for a first
     * haircut, which happens when it happens.
     *
     * Coming from the catalogue is not the test: most guided milestones are firsts.
     */
    public function isDateEditable(): bool
    {
        return $this->is_date_editable;
    }

    /**
     * Where this milestone sits is not the parent's to change.
     *
     * Two reasons, and they land on the same answer: one that names a date is that
     * date, and one the child has not reached is a stretch of map that has not
     * happened yet. Rearranging either arranges a preview.
     */
    public function isPinned(?Child $child = null): bool
    {
        return ! $this->isDateEditable() || ($child !== null && $this->isLockedFor($child));
    }

    /**
     * When this milestone falls — the birthday plus however long it names.
     *
     * What differs is the weight it carries: a milestone whose date is not editable
     * lands here exactly and a memory filed against it takes this and nothing else,
     * while a first is only estimated here and the parent moves it.
     */
    public function happensFor(Child $child): ?CarbonImmutable
    {
        if ($this->happens_after === null || $this->happens_unit === null) {
            return null;
        }

        return $this->happens_unit->after(
            CarbonImmutable::parse($child->birthday),
            $this->happens_after,
        );
    }

    /**
     * The day a memory must take, or null when the parent is free to choose.
     */
    public function dateFor(Child $child): ?CarbonImmutable
    {
        return $this->isDateEditable() ? null : $this->happensFor($child);
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
     * done to it. is_date_editable does, and only over position: a milestone that
     * names a date cannot be carried to a chapter that is not that date.
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
            'retime' => $mayWrite && ! $sealed && $this->isDateEditable() && ! $locked && ! $recorded,
            'setDate' => $mayWrite && $this->isDateEditable(),
            'delete' => $mayWrite && $this->isDeletable(),
            'record' => $mayWrite && ! $recorded && ! $outOfReach,
        ];
    }
}
