<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\AppSettingKey;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Inertia\Inertia;
use Inertia\Response;

class ShowSettingsController extends Controller
{
    /** What each number actually does, so the admin is not guessing. */
    private const HINTS = [
        'daily_free_entries' => 'Free memories a parent can capture in one day. One keeps it precious.',
        'free_entry_xp' => 'XP a free memory is worth. Step memories are worth their own step instead.',
        'daily_step_entries' => 'Step memories a parent can record in one day.',
        'max_custom_steps_per_milestone' => 'How many of their own steps a parent can add to one chapter.',
        'min_steps_to_complete_milestone' => 'Visible steps a chapter needs before it can be finished — the guard against hiding a chapter down to a handful and collecting the gift.',
    ];

    public function __invoke(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => collect(AppSettingKey::cases())->map(fn (AppSettingKey $key) => [
                'key' => $key->value,
                'value' => AppSetting::number($key),
                'default' => $key->default(),
                'hint' => self::HINTS[$key->value] ?? null,
            ]),
        ]);
    }
}
