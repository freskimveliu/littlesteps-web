<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['template_step_id', 'key', 'name', 'sort_order'])]
class TemplateStepProperty extends Model
{
    protected function casts(): array
    {
        return ['key' => PropertyKey::class];
    }

    /** @return BelongsTo<TemplateStep, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(TemplateStep::class, 'template_step_id');
    }
}
