<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateChapterController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildChapter $chapter): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);
        abort_if($chapter->isCompleted(), 403, 'This chapter is finished — its map cannot change.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
        ]);

        $chapter->update([...$validated, 'updated_by_user_id' => $request->user()->id]);

        return ApiResponse::success(
            new ChildChapterResource($chapter->fresh()->load([
                'child',
                'milestones' => fn ($q) => $q->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])),
            'Saved.',
        );
    }
}
