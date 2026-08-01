<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The whole adventure map in one call — every chapter, its milestones, their
 * properties and any memory already written, plus the flags the app must not
 * work out for itself.
 */
class IndexChaptersController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $chapters = $child->chapters()
            ->visible()
            ->with([
                'child',
                'milestones' => fn ($q) => $q->visible()->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(ChildChapterResource::collection($chapters));
    }
}
