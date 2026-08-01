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

class StoreEntryMediaController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry, Limits $limits): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', ChildEntry::ACCEPTS), 'max:20480'],
        ]);

        $most = $limits->maxMediaPerEntry();

        if ($entry->mediaCount() >= $most) {
            throw ValidationException::withMessages([
                'file' => $most === 1
                    ? 'A memory holds one attachment.'
                    : "A memory holds up to {$most} attachments.",
            ]);
        }

        $entry->addMediaFromRequest('file')->toMediaCollection(ChildEntry::MEDIA);

        return ApiResponse::success(
            new ChildEntryResource($entry->fresh()->load(['milestone', 'properties', 'media'])->bindMediaOwner()),
            'Added.',
            201,
        );
    }
}
