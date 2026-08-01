<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use Illuminate\Http\RedirectResponse;

/**
 * Soft delete only: children already provisioned keep a chapter_id
 * pointing here, and a hard delete would break that trail.
 */
class DestroyChapterController extends Controller
{
    public function __invoke(Chapter $chapter): RedirectResponse
    {
        $chapter->delete();

        return back()->with('success', 'Chapter removed from the catalogue.');
    }
}
