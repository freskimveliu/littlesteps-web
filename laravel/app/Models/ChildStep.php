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
    'child_id', 'child_milestone_id', 'template_step_id', 'category_id',
    'name', 'icon', 'months_from', 'xp', 'sort_order',
    'is_editable', 'is_hidden', 'created_by_user_id', 'updated_by_user_id',
])]
class ChildStep extends Model
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

    /** @return BelongsTo<ChildMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ChildMilestone::class, 'child_milestone_id');
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

    /** @return HasMany<ChildStepProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(ChildStepProperty::class);
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
     * A step is only deletable while it is still empty. Once an entry exists the
     * XP has been taken, and deleting would hand back a free daily slot too.
     */
    public function isDeletable(): bool
    {
        return $this->is_editable && ! $this->isRecorded();
    }

    /** @param Builder<$this> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
