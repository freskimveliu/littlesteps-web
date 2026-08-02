<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'child_id', 'chapter_id', 'name', 'description', 'icon',
    'months_from', 'xp', 'sort_order', 'is_editable', 'is_hidden',
    'created_by_user_id', 'updated_by_user_id',
])]
class ChildChapter extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_editable' => 'boolean',
            'is_hidden' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return HasMany<ChildMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ChildMilestone::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * A chapter can go while it is still unfinished — a guided one included, since
     * a parent who found the app at a year old has months of map behind them that
     * no photo will ever fill. Once it is finished its gift has been given, and
     * the last chapter always stays: an empty map is not a journey.
     */
    public function isDeletable(): bool
    {
        return ! $this->isCompleted() && $this->child->chapterCount() > 1;
    }

    public function isUnlockedFor(Child $child): bool
    {
        return $this->months_from === null || $child->ageInMonths() >= $this->months_from;
    }
}
