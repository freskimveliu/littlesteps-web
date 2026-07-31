<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Steps;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildStepResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HideStepController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildStep $step): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($step->child_id === $child->id, 404);

        $hidden = $request->boolean('hidden', true);

        $step->update([
            'is_hidden' => $hidden,
            'updated_by_user_id' => $request->user()->id,
        ]);

        return ApiResponse::success(
            new ChildStepResource($step->fresh()->load(['category', 'properties', 'entry', 'child'])),
            $hidden ? 'Step hidden.' : 'Step restored.',
        );
    }
}
