<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Models\ChildReward;
use Illuminate\Http\RedirectResponse;

/**
 * Puts a stuck or failed gift back to unclaimed so the parent can ask for it
 * again. The badge itself is untouched — it was earned, and stays earned.
 */
class ResetGiftController extends Controller
{
    public function __invoke(ChildReward $gift): RedirectResponse
    {
        $gift->update([
            'status' => RewardStatus::Unclaimed,
            'claimed_at' => null,
            'generated_at' => null,
        ]);

        return back()->with('success', 'Gift reset. The parent can claim it again.');
    }
}
