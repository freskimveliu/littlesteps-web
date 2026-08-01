<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Support\Limits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreEntryPhotoController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry, Limits $limits): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $request->validate([
            'photo' => ['required', 'image', 'max:20480'],
        ]);

        $most = $limits->maxMediaPerEntry();

        if ($entry->photoCount() >= $most) {
            throw ValidationException::withMessages([
                'photo' => $most === 1
                    ? 'A memory holds one photo.'
                    : "A memory holds up to {$most} photos.",
            ]);
        }

        $entry->addMediaFromRequest('photo')->toMediaCollection(ChildEntry::PHOTOS);

        return ApiResponse::success(
            new ChildEntryResource($entry->fresh()->load(['milestone', 'properties', 'media'])->bindMediaOwner()),
            'Photo added.',
            201,
        );
    }
}
