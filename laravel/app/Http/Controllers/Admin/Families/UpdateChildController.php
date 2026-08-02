<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChildRequest;
use App\Models\Child;
use Illuminate\Http\RedirectResponse;

class UpdateChildController extends Controller
{
    public function __invoke(ChildRequest $request, Child $child): RedirectResponse
    {
        $child->update($request->validated());

        return back()->with('success', 'Child saved.');
    }
}
