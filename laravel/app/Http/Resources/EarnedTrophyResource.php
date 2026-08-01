<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildTrophy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trophy the child already holds, rendered from the copy taken when it was
 * unlocked — so it reads the same next year as it did on the day.
 *
 * @mixin ChildTrophy
 */
class EarnedTrophyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->trophy_id,
            'earnedId' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'metric' => $this->metric,
            'threshold' => $this->threshold,
            'xp' => $this->xp,
            'reward' => $this->reward,
            'carriesGift' => $this->carriesGift(),
            'isUnlocked' => true,
            'progress' => $this->threshold,
            'ratio' => 1.0,
            'unlockedAt' => $this->unlocked_at?->toIso8601String(),
        ];
    }
}
