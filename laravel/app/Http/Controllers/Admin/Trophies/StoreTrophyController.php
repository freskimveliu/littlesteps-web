<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Trophies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrophyRequest;
use App\Models\Trophy;
use Illuminate\Http\RedirectResponse;

class StoreTrophyController extends Controller
{
    public function __invoke(TrophyRequest $request): RedirectResponse
    {
        Trophy::create($request->validated());

        return back()->with('success', 'Trophy created.');
    }
}
