<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\AppSettingKey;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateSettingsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            if (! AppSettingKey::tryFrom((string) $key)) {
                continue;
            }

            AppSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
