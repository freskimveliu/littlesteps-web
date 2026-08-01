<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Badges;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\RedirectResponse;

class DestroyBadgeController extends Controller
{
    public function __invoke(Achievement $badge): RedirectResponse
    {
        $badge->delete();

        return back()->with('success', 'Badge removed.');
    }
}
