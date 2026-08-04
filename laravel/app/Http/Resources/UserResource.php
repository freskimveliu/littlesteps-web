<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'shareCode' => $this->share_code,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'isRegistered' => $this->isRegistered(),
            'isAdmin' => $this->is_admin,
            'currentStreak' => $this->current_streak,
            'longestStreak' => $this->longest_streak,
            'lastEntryDate' => $this->last_entry_date?->toDateString(),
            'photo' => $this->photoUrl() ? [
                'url' => $this->photoUrl(),
                'thumb' => $this->photoThumbUrl(),
            ] : null,
            'settings' => $this->settingsMap(),
        ];
    }
}
