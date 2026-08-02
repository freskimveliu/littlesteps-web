<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'child_id', 'child_chapter_id', 'milestone_id', 'category_id',
    'name', 'icon', 'months_from', 'xp', 'sort_order',
    'is_editable', 'is_hidden', 'created_by_user_id', 'updated_by_user_id',
])]
class ChildMilestone extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_editable' => 'boolean',
            'is_hidden' => 'boolean',
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

    /**
     * A milestone is only deletable while it is still empty. Once an entry exists the
     * XP has been taken, and deleting would hand back a free daily slot too.
     *
     * A guided milestone is no exception: not every child has siblings or a
     * christening, and a map cannot be finished while it carries a node that will
     * never happen.
     */
    public function isDeletable(): bool
    {
        return ! $this->isRecorded();
    }

    /**
     * Everything a parent may do to this milestone, decided here so the app never
     * has to know the rules — it renders the buttons this says it can.
     *
     * `reorder` is permission, not position: whether a neighbour exists to swap
     * with is a question about the list the app is already holding.
     *
     * The map is the parent's, guided or not — a name that does not match the child
     * is worse than no name. is_editable records where the row came from; it does
     * not decide what may be done to it.
     *
     * @return array<string, bool>
     */
    public function abilities(): array
    {
        $child = $this->child;
        $locked = $child ? $this->isLockedFor($child) : false;
        $recorded = $this->isRecorded();

        return [
            'rename' => true,
            'move' => true,
            'reorder' => true,
            'delete' => $this->isDeletable(),
            'skip' => ! $recorded && ! $this->is_hidden,
            'unskip' => $this->is_hidden,
            'record' => ! $recorded && ! $locked && ! $this->is_hidden,
        ];
    }

    /** @param Builder<$this> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
