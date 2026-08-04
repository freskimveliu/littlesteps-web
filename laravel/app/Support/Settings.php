<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AppSettingKey;
use App\Models\AppSetting;

/**
 * The numbers that pace the app, read once per request instead of once per ask —
 * the map alone was fetching the same handful of rows a dozen times over.
 */
class Settings
{
    /** @var array<string, string>|null */
    private ?array $values = null;

    public function number(AppSettingKey $key): int
    {
        $this->values ??= AppSetting::query()->pluck('value', 'key')->all();

        return isset($this->values[$key->value])
            ? (int) $this->values[$key->value]
            : $key->default();
    }

    /** Called on every AppSetting write, so the console never shows a stale number. */
    public function flush(): void
    {
        $this->values = null;
    }
}
