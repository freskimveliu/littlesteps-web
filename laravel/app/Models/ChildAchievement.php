<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['child_id', 'achievement_id', 'unlocked_at'])]
class ChildAchievement extends Model
{
    protected function casts(): array
    {
        return ['unlocked_at' => 'datetime'];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<Achievement, $this> */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    /** @return HasOne<ChildReward, $this> */
    public function reward(): HasOne
    {
        return $this->hasOne(ChildReward::class);
    }
}
