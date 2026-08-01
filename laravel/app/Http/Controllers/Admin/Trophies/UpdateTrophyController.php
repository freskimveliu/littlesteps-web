<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Trophies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrophyRequest;
use App\Models\Trophy;
use Illuminate\Http\RedirectResponse;

/**
 * Retuning a threshold never takes a trophy back: child_trophies rows are
 * written once and read from there, not recomputed against this rule.
 */
class UpdateTrophyController extends Controller
{
    public function __invoke(TrophyRequest $request, Trophy $trophy): RedirectResponse
    {
        $trophy->update($request->validated());

        return back()->with('success', 'Trophy saved. Children who already earned it keep it.');
    }
}
