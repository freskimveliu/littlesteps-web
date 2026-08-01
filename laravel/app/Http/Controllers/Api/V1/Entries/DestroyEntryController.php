<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A memory attached to a milestone is permanent — it can be edited, never deleted.
 * Otherwise deleting would hand back the XP-bearing slot and the milestone could be
 * re-recorded for XP again. A free memory has no such hold, so it can go.
 */
class DestroyEntryController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        abort_unless(
            $entry->isDeletable(),
            403,
            'This memory belongs to a milestone. You can edit it, but it stays in the story.',
        );

        $entry->delete();

        return ApiResponse::noContent();
    }
}
