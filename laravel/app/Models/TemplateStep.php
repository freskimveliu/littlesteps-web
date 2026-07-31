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
    'slug', 'template_milestone_id', 'category_id', 'name', 'description',
    'icon', 'months_from', 'xp', 'sort_order', 'is_editable', 'is_active',
])]
class TemplateStep extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_editable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<TemplateMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(TemplateMilestone::class, 'template_milestone_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<TemplateStepProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(TemplateStepProperty::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
