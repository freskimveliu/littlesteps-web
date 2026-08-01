<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Levels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LevelRequest;
use App\Models\Level;
use Illuminate\Http\RedirectResponse;

class UpdateLevelController extends Controller
{
    public function __invoke(LevelRequest $request, Level $level): RedirectResponse
    {
        $level->update($request->validated());

        return back()->with('success', 'Level saved.');
    }
}
