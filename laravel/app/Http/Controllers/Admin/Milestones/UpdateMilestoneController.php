<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MilestoneRequest;
use App\Models\Milestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UpdateMilestoneController extends Controller
{
    public function __invoke(MilestoneRequest $request, Milestone $milestone): RedirectResponse
    {
        DB::transaction(function () use ($request, $milestone) {
            $milestone->update($request->safe()->except('properties'));

            // Positional rather than keyed, so the set is replaced wholesale.
            $milestone->properties()->delete();

            foreach ($request->array('properties') as $i => $property) {
                $milestone->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }
        });

        return back()->with('success', 'Milestone saved. Children already provisioned keep their own copy.');
    }
}
