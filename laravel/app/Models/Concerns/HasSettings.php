<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait HasSettings
{
    /** @return MorphMany<Setting, $this> */
    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settable');
    }

    public function setting(SettingKey $key): bool
    {
        $stored = $this->settings->firstWhere('key', $key)?->value;

        return $stored === null ? $key->default() : filter_var($stored, FILTER_VALIDATE_BOOLEAN);
    }

    public function putSetting(SettingKey $key, bool $value): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key->value],
            ['value' => $value ? '1' : '0'],
        );

        $this->unsetRelation('settings');
    }

    /**
     * Keyed in camelCase for the payload; the enum spells them the way the column
     * does. A patch back to /auth/me is read either way — see SettingKey::match().
     *
     * @return array<string, bool>
     */
    public function settingsMap(): array
    {
        return collect(SettingKey::cases())
            ->mapWithKeys(fn (SettingKey $key) => [Str::camel($key->value) => $this->setting($key)])
            ->all();
    }
}
