<?php

declare(strict_types=1);

namespace App\Actions\Progress;

use App\Enums\RewardStatus;
use App\Models\ChildReward;

/**
 * Claiming is what starts a generation, never earning the trophy — so a lapsed
 * account never costs one, and a failure can be retried without a duplicate.
 *
 * TODO: dispatch the generation job once the story/image/book prompts exist.
 * Until then the row moves to generating and waits.
 */
class ClaimReward
{
    public function handle(ChildReward $reward): ChildReward
    {
        abort_unless($reward->status->isClaimable(), 409, 'This gift is already on its way.');

        $reward->update([
            'status' => RewardStatus::Generating,
            'claimed_at' => $reward->claimed_at ?? now(),
        ]);

        return $reward->fresh();
    }
}
