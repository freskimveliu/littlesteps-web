<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_entry_id', 'key', 'name', 'value', 'sort_order'])]
class ChildEntryProperty extends Model
{
    protected function casts(): array
    {
        return ['key' => PropertyKey::class];
    }

    /** @return BelongsTo<ChildEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ChildEntry::class, 'child_entry_id');
    }

    /** @param Builder<$this> $query */
    public function scopeChartable(Builder $query): void
    {
        $query->whereIn('key', [PropertyKey::Weight->value, PropertyKey::Length->value]);
    }
}
