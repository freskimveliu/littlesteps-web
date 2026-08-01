<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildReward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildReward */
class RewardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $artwork = $this->getFirstMedia(ChildReward::ARTWORK);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'isClaimable' => $this->status->isClaimable(),
            'content' => $this->content,
            'artwork' => $artwork ? [
                'url' => $artwork->getUrl(),
                'thumb' => $artwork->getUrl('thumb'),
            ] : null,
            'trophy' => new EarnedTrophyResource($this->whenLoaded('childTrophy')),
            'claimedAt' => $this->claimed_at?->toIso8601String(),
            'generatedAt' => $this->generated_at?->toIso8601String(),
        ];
    }
}
