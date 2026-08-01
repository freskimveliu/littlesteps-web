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
        'free_entry_xp' => 'XP a free memory is worth. Milestone memories are worth their own milestone instead.',
        'daily_milestone_entries' => 'Milestone memories a parent can record in one day.',
        'max_custom_milestones_per_chapter' => 'How many of their own milestones a parent can add to one chapter.',
        'min_milestones_to_complete_chapter' => 'Visible milestones a chapter needs before it can be finished — the guard against hiding a chapter down to a handful and collecting the gift.',
        'max_media_per_entry' => 'Attachments one memory can hold — photos today, whatever a memory may carry later.',
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
