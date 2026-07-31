<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['settable_type', 'settable_id', 'key', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return ['key' => SettingKey::class];
    }

    /**
     * Named owner() rather than settable(): PHP method names are
     * case-insensitive, so settable() would collide with Model::setTable().
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('settable');
    }
}
