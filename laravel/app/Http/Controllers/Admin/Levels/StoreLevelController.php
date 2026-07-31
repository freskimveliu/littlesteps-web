<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Levels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LevelRequest;
use App\Models\TemplateLevel;
use Illuminate\Http\RedirectResponse;

class StoreLevelController extends Controller
{
    public function __invoke(LevelRequest $request): RedirectResponse
    {
        TemplateLevel::create($request->validated());

        return back()->with('success', 'Level created.');
    }
}
