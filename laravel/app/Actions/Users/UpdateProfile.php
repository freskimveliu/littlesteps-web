<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\ProfileData;
use App\Enums\SettingKey;
use App\Models\User;
use App\Support\SmallerOriginal;
use Illuminate\Support\Facades\DB;

/**
 * One screen, one write. A setting that is not a SettingKey is dropped rather
 * than refused, so an older server takes the switches it understands instead of
 * rejecting the lot.
 */
class UpdateProfile
{
    public function handle(User $user, ProfileData $data): User
    {
        DB::transaction(function () use ($user, $data) {
            $user->fill($data->toAttributes())->save();

            foreach ($data->settings as $key => $value) {
                if ($setting = SettingKey::match((string) $key)) {
                    $user->putSetting($setting, (bool) $value);
                }
            }
        });

        // Outside it — see CreateChild.
        if ($data->photo) {
            $user->addMedia(SmallerOriginal::of($data->photo))->toMediaCollection(User::PHOTO);
        }

        return $user->fresh()->load('settings');
    }
}
