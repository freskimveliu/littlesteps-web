<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'icon', 'color', 'sort_order', 'is_active'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TemplateStep, $this> */
    public function templateSteps(): HasMany
    {
        return $this->hasMany(TemplateStep::class);
    }
}
