<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'name', 'icon', 'months_from', 'months_to', 'sort_order', 'is_active'])]
class Prompt extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Prompts with no range suit any age; the rest have to bracket the child,
     * or a newborn question would still be showing at five years old.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForAge(Builder $query, int $months): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('months_from')->orWhere('months_from', '<=', $months))
            ->where(fn (Builder $q) => $q->whereNull('months_to')->orWhere('months_to', '>', $months));
    }
}
