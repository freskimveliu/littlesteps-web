<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MilestoneRequest;
use App\Models\Milestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreMilestoneController extends Controller
{
    public function __invoke(MilestoneRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $milestone = Milestone::create($request->safe()->except('properties'));

            foreach ($request->array('properties') as $i => $property) {
                $milestone->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }
        });

        return back()->with('success', 'Milestone created.');
    }
}
