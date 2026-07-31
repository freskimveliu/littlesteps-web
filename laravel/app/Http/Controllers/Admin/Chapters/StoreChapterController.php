<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChapterRequest;
use App\Models\TemplateMilestone;
use Illuminate\Http\RedirectResponse;

class StoreChapterController extends Controller
{
    public function __invoke(ChapterRequest $request): RedirectResponse
    {
        TemplateMilestone::create($request->validated());

        return back()->with('success', 'Chapter created.');
    }
}
