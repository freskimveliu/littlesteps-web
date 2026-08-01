<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use App\Enums\RewardType;
use App\Enums\TrophyMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A trophy as this child earned it — the wording and the rule copied at the
 * moment it was unlocked, so retuning the catalogue later cannot rewrite what
 * a parent was already shown. Read from these columns, never through trophy().
 */
#[Fillable([
    'child_id', 'trophy_id', 'name', 'description', 'icon', 'metric',
    'threshold', 'xp', 'reward', 'sort_order', 'unlocked_at',
])]
class ChildTrophy extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'metric' => TrophyMetric::class,
            'reward' => RewardType::class,
            'unlocked_at' => 'datetime',
        ];
    }

    public function carriesGift(): bool
    {
        return $this->reward !== null;
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * The catalogue row this was copied from — null once an admin deletes it.
     * Kept so the same trophy cannot be awarded twice, not to read from.
     *
     * @return BelongsTo<Trophy, $this>
     */
    public function trophy(): BelongsTo
    {
        return $this->belongsTo(Trophy::class, 'trophy_id');
    }

    /** @return HasOne<ChildReward, $this> */
    public function gift(): HasOne
    {
        return $this->hasOne(ChildReward::class);
    }
}
