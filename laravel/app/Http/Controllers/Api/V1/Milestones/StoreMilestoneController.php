<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMilestoneRequest;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Support\Limits;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StoreMilestoneController extends Controller
{
    public function __invoke(StoreMilestoneRequest $request, Child $child, Limits $limits): JsonResponse
    {
        $this->authorize('contribute', $child);

        $chapter = $child->chapters()->findOrFail($request->integer('child_chapter_id'));

        if (! $chapter->isUnlockedFor($child)) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'That chapter has not opened yet.',
            ]);
        }

        if (! $limits->canAddCustomMilestone($chapter)) {
            throw ValidationException::withMessages([
                'child_chapter_id' => 'This chapter already has as many of your own milestones as it can hold.',
            ]);
        }

        $milestone = $child->milestones()->create([
            'child_chapter_id' => $chapter->id,
            'category_id' => $request->integer('category_id') ?: null,
            'name' => $request->string('name')->toString(),
            'icon' => $request->input('icon'),
            'months_from' => $request->input('months_from') ?? $chapter->months_from,
            'xp' => 20,
            'sort_order' => ($chapter->milestones()->max('sort_order') ?? 0) + 10,
            'is_editable' => true,
            'created_by_user_id' => $request->user()->id,
        ]);

        foreach ($request->array('properties') as $i => $property) {
            $milestone->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
        }

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->load(['category', 'properties', 'child'])),
            'Milestone added.',
            201,
        );
    }
}
