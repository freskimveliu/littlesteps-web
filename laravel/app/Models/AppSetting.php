<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppSettingKey;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    protected function casts(): array
    {
        return ['key' => AppSettingKey::class];
    }

    /** Read through Settings, which holds them for the request. */
    public static function number(AppSettingKey $key): int
    {
        return app(Settings::class)->number($key);
    }

    protected static function booted(): void
    {
        $forget = fn () => app(Settings::class)->flush();

        static::saved($forget);
        static::deleted($forget);
    }
}
