<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChapterRequest;
use App\Models\Chapter;
use Illuminate\Http\RedirectResponse;

class UpdateChapterController extends Controller
{
    public function __invoke(ChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $chapter->update($request->validated());

        return back()->with('success', 'Chapter saved.');
    }
}
