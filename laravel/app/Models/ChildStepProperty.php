<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_step_id', 'key', 'name', 'sort_order'])]
class ChildStepProperty extends Model
{
    protected function casts(): array
    {
        return ['key' => PropertyKey::class];
    }

    public function label(): string
    {
        return $this->name ?? ucfirst($this->key->value);
    }

    /** @return BelongsTo<ChildStep, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ChildStep::class, 'child_step_id');
    }
}
