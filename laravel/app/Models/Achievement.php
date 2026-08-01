<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AchievementMetric;
use App\Enums\Icon;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'description', 'icon', 'metric',
    'threshold', 'xp', 'reward', 'sort_order', 'is_active',
])]
class Achievement extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'metric' => AchievementMetric::class,
            'reward' => RewardType::class,
            'is_active' => 'boolean',
        ];
    }

    public function carriesGift(): bool
    {
        return $this->reward !== null;
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
