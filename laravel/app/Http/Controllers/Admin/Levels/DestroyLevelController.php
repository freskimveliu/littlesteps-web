<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Levels;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\RedirectResponse;

class DestroyLevelController extends Controller
{
    public function __invoke(Level $level): RedirectResponse
    {
        if ($level->min_xp === 0) {
            return back()->with('error', 'The first level is where every child starts. It cannot be removed.');
        }

        $level->delete();

        return back()->with('success', 'Level removed.');
    }
}
