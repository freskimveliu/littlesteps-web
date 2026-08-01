<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Trophies;

use App\Http\Controllers\Controller;
use App\Models\Trophy;
use Illuminate\Http\RedirectResponse;

class DestroyTrophyController extends Controller
{
    public function __invoke(Trophy $trophy): RedirectResponse
    {
        $trophy->delete();

        return back()->with('success', 'Trophy removed.');
    }
}
