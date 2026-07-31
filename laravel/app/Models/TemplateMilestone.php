<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['slug', 'name', 'description', 'icon', 'months_from', 'xp', 'sort_order', 'is_editable', 'is_active'])]
class TemplateMilestone extends Model
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

    /** @return HasMany<TemplateStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(TemplateStep::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
