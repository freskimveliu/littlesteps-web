<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Badges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BadgeRequest;
use App\Models\TemplateAchievement;
use Illuminate\Http\RedirectResponse;

/**
 * Retuning a threshold never takes a badge back: child_achievements rows are
 * written once and read from there, not recomputed against this rule.
 */
class UpdateBadgeController extends Controller
{
    public function __invoke(BadgeRequest $request, TemplateAchievement $badge): RedirectResponse
    {
        $badge->update($request->validated());

        return back()->with('success', 'Badge saved. Children who already earned it keep it.');
    }
}
