<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Steps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StepRequest;
use App\Models\TemplateStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreStepController extends Controller
{
    public function __invoke(StepRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $step = TemplateStep::create($request->safe()->except('properties'));

            foreach ($request->array('properties') as $i => $property) {
                $step->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }
        });

        return back()->with('success', 'Step created.');
    }
}
