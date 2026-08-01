<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateEntryRequest;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Editing is the only correction path for a memory attached to a milestone, so it
 * has to cover everything: the date, the words, the mood and the measurements.
 */
class UpdateEntryController extends Controller
{
    public function __invoke(UpdateEntryRequest $request, Child $child, ChildEntry $entry): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        DB::transaction(function () use ($request, $entry) {
            $entry->update([
                ...$request->safe()->except('properties'),
                'updated_by_user_id' => $request->user()->id,
            ]);

            if ($request->has('properties')) {
                $entry->properties()->delete();

                foreach ($request->array('properties') as $i => $property) {
                    $entry->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
                }
            }
        });

        return ApiResponse::success(
            new ChildEntryResource($entry->fresh()->load(['milestone', 'properties', 'media'])),
            'Saved.',
        );
    }
}
