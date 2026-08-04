<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'chapter_id', 'category_id', 'name', 'description',
    'icon', 'months_from', 'typical_days', 'is_dated', 'xp', 'sort_order', 'is_editable', 'is_active',
])]
class Milestone extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_dated' => 'boolean',
            'is_editable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Chapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<MilestoneProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(MilestoneProperty::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
