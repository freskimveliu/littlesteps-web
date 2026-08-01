<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_milestone_id', 'key', 'name', 'sort_order'])]
class ChildMilestoneProperty extends Model
{
    protected function casts(): array
    {
        return ['key' => PropertyKey::class];
    }

    public function label(): string
    {
        return $this->name ?? ucfirst($this->key->value);
    }

    /** @return BelongsTo<ChildMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ChildMilestone::class, 'child_milestone_id');
    }
}
