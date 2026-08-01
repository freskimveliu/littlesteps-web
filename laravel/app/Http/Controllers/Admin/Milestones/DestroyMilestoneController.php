<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Milestones;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use Illuminate\Http\RedirectResponse;

class DestroyMilestoneController extends Controller
{
    public function __invoke(Milestone $milestone): RedirectResponse
    {
        $milestone->delete();

        return back()->with('success', 'Milestone removed from the catalogue.');
    }
}
