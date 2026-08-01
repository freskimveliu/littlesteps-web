<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppSettingKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    protected function casts(): array
    {
        return ['key' => AppSettingKey::class];
    }

    public static function number(AppSettingKey $key): int
    {
        $stored = static::query()->where('key', $key->value)->value('value');

        return $stored === null ? $key->default() : (int) $stored;
    }
}
