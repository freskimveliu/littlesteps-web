<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The whole adventure map in one call — every chapter, its steps, their
 * properties and any memory already written, plus the flags the app must not
 * work out for itself.
 */
class IndexMilestonesController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $milestones = $child->milestones()
            ->visible()
            ->with([
                'child',
                'steps' => fn ($q) => $q->visible()->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(ChildMilestoneResource::collection($milestones));
    }
}
