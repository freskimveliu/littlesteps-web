<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Steps;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A step can only be deleted while it is still empty.
 *
 * Once a memory exists the XP has been taken and a daily slot has been spent;
 * allowing a delete then would hand both back. A parent who wants a recorded
 * step off the map hides it instead.
 */
class DestroyStepController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildStep $step): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($step->child_id === $child->id, 404);

        abort_unless(
            $step->isDeletable(),
            403,
            $step->isRecorded()
                ? 'This step already holds a memory. Hide it instead.'
                : 'This step is part of the guided journey.',
        );

        $step->delete();

        return ApiResponse::noContent();
    }
}
