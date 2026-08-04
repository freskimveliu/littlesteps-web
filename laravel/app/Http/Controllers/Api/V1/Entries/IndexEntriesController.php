<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexEntriesRequest;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class IndexEntriesController extends Controller
{
    public function __invoke(IndexEntriesRequest $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $entries = $child->entries()
            ->with(['milestone', 'properties', 'media', 'creator', 'editor'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($request->perPage());

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
