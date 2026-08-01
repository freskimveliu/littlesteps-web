<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TemplateAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A badge as the child sees it — the rule, plus how far along they are and
 * whether it carries a gift.
 *
 * @mixin TemplateAchievement
 */
class AchievementResource extends JsonResource
{
    public function __construct($resource, private readonly int $progress = 0, private readonly bool $unlocked = false)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'metric' => $this->metric,
            'threshold' => $this->threshold,
            'xp' => $this->xp,
            'reward' => $this->reward,
            'carriesGift' => $this->carriesGift(),
            'isUnlocked' => $this->unlocked,
            'progress' => min($this->progress, $this->threshold),
            'ratio' => $this->threshold > 0 ? round(min($this->progress, $this->threshold) / $this->threshold, 4) : 1.0,
        ];
    }
}
