<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Badges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BadgeRequest;
use App\Models\TemplateAchievement;
use Illuminate\Http\RedirectResponse;

class StoreBadgeController extends Controller
{
    public function __invoke(BadgeRequest $request): RedirectResponse
    {
        TemplateAchievement::create($request->validated());

        return back()->with('success', 'Badge created.');
    }
}
