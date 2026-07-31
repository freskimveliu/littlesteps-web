<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChapterRequest;
use App\Models\TemplateMilestone;
use Illuminate\Http\RedirectResponse;

class UpdateChapterController extends Controller
{
    public function __invoke(ChapterRequest $request, TemplateMilestone $chapter): RedirectResponse
    {
        $chapter->update($request->validated());

        return back()->with('success', 'Chapter saved.');
    }
}
