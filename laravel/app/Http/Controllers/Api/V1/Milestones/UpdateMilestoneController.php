<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateMilestoneController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildMilestone $milestone): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);
        abort_unless($milestone->is_editable, 403, 'This milestone is part of the guided journey.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
            'child_chapter_id' => ['sometimes', 'integer'],
        ]);

        if (isset($validated['child_chapter_id'])) {
            $chapter = $child->chapters()->findOrFail($validated['child_chapter_id']);

            // A chapter the child has not grown into yet is a preview, not a
            // shelf — dropping a milestone in there would hide it from them.
            if (! $chapter->isUnlockedFor($child)) {
                throw ValidationException::withMessages([
                    'child_chapter_id' => 'That chapter has not opened yet.',
                ]);
            }
        }

        $milestone->update([...$validated, 'updated_by_user_id' => $request->user()->id]);

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->fresh()->load(['category', 'properties', 'entry', 'child'])),
            'Saved.',
        );
    }
}
