<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexEntriesController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $entries = $child->entries()
            ->with(['milestone', 'properties', 'media'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 30));

        $entries->getCollection()->each->bindMediaOwner();

        return ApiResponse::success([
            'items' => ChildEntryResource::collection($entries)->resolve(),
            'meta' => [
                'page' => $entries->currentPage(),
                'perPage' => $entries->perPage(),
                'total' => $entries->total(),
                'lastPage' => $entries->lastPage(),
            ],
        ]);
    }
}
