<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Steps;

use App\Http\Controllers\Controller;
use App\Models\TemplateStep;
use Illuminate\Http\RedirectResponse;

class DestroyStepController extends Controller
{
    public function __invoke(TemplateStep $step): RedirectResponse
    {
        $step->delete();

        return back()->with('success', 'Step removed from the catalogue.');
    }
}
