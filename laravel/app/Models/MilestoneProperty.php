<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['milestone_id', 'key', 'name', 'sort_order'])]
class MilestoneProperty extends Model
{
    protected function casts(): array
    {
        return ['key' => PropertyKey::class];
    }

    /** @return BelongsTo<Milestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }
}
