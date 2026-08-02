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
     * never happen. It cannot be renamed or moved — that is what is_editable
     * guards — but it can be taken off this child's map for good.
     */
    public function isDeletable(): bool
    {
        return ! $this->isRecorded();
    }

    /** @param Builder<$this> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
