<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Steps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StepRequest;
use App\Models\TemplateStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UpdateStepController extends Controller
{
    public function __invoke(StepRequest $request, TemplateStep $step): RedirectResponse
    {
        DB::transaction(function () use ($request, $step) {
            $step->update($request->safe()->except('properties'));

            // Positional rather than keyed, so the set is replaced wholesale.
            $step->properties()->delete();

            foreach ($request->array('properties') as $i => $property) {
                $step->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
            }
        });

        return back()->with('success', 'Step saved. Children already provisioned keep their own copy.');
    }
}
