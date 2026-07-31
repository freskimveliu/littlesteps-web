<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Steps;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChildStepResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateStepController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildStep $step): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($step->child_id === $child->id, 404);
        abort_unless($step->is_editable, 403, 'This step is part of the guided journey.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
            'child_milestone_id' => ['sometimes', 'integer'],
        ]);

        if (isset($validated['child_milestone_id'])) {
            $child->milestones()->findOrFail($validated['child_milestone_id']);
        }

        $step->update([...$validated, 'updated_by_user_id' => $request->user()->id]);

        return ApiResponse::success(
            new ChildStepResource($step->fresh()->load(['category', 'properties', 'entry', 'child'])),
            'Saved.',
        );
    }
}
