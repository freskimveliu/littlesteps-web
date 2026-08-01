<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The whole running order in one call rather than a nudge per chapter: two
 * parents each moving something a place would otherwise land on the same
 * sort_order and the map would shuffle itself.
 */
class ReorderChaptersController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('contribute', $child);

        $validated = $request->validate([
            'chapters' => ['required', 'array', 'min:1'],
            'chapters.*' => ['integer'],
        ]);

        $ordered = collect($validated['chapters']);
        $own = $child->chapters()->pluck('id');

        if ($ordered->duplicates()->isNotEmpty() || $ordered->diff($own)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'chapters' => 'That list does not match this child’s chapters.',
            ]);
        }

        DB::transaction(function () use ($ordered, $child, $request) {
            foreach ($ordered as $i => $id) {
                $child->chapters()->whereKey($id)->update([
                    'sort_order' => ($i + 1) * 10,
                    'updated_by_user_id' => $request->user()->id,
                ]);
            }
        });

        $chapters = $child->chapters()
            ->with([
                'child',
                'milestones' => fn ($q) => $q->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(ChildChapterResource::collection($chapters), 'Reordered.');
    }
}
