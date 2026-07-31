<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Steps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStepRequest;
use App\Http\Resources\ChildStepResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Support\Limits;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StoreStepController extends Controller
{
    public function __invoke(StoreStepRequest $request, Child $child, Limits $limits): JsonResponse
    {
        $this->authorize('contribute', $child);

        $milestone = $child->milestones()->findOrFail($request->integer('child_milestone_id'));

        if (! $limits->canAddCustomStep($milestone)) {
            throw ValidationException::withMessages([
                'child_milestone_id' => 'This chapter already has as many of your own steps as it can hold.',
            ]);
        }

        $step = $child->steps()->create([
            'child_milestone_id' => $milestone->id,
            'category_id' => $request->integer('category_id') ?: null,
            'name' => $request->string('name')->toString(),
            'icon' => $request->input('icon'),
            'months_from' => $request->input('months_from') ?? $milestone->months_from,
            'xp' => 20,
            'sort_order' => ($milestone->steps()->max('sort_order') ?? 0) + 10,
            'is_editable' => true,
            'created_by_user_id' => $request->user()->id,
        ]);

        foreach ($request->array('properties') as $i => $property) {
            $step->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
        }

        return ApiResponse::success(
            new ChildStepResource($step->load(['category', 'properties', 'child'])),
            'Step added.',
            201,
        );
    }
}
