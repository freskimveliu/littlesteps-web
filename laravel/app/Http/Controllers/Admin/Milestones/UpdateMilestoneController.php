<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MilestoneRequest;
use App\Models\TemplateMilestone;
use Illuminate\Http\RedirectResponse;

class UpdateMilestoneController extends Controller
{
    public function __invoke(MilestoneRequest $request, TemplateMilestone $milestone): RedirectResponse
    {
        $milestone->update($request->validated());

        return back()->with('success', 'Milestone saved.');
    }
}
