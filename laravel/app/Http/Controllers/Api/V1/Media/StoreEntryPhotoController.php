<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreEntryPhotoController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $request->validate([
            'photo' => ['required', 'image', 'max:20480'],
        ]);

        $entry->addMediaFromRequest('photo')->toMediaCollection(ChildEntry::PHOTOS);

        return ApiResponse::success(
            new ChildEntryResource($entry->fresh()->load(['step', 'properties', 'media'])),
            'Photo added.',
            201,
        );
    }
}
