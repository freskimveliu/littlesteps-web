<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DestroyEntryMediaController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry, int $media): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $file = $entry->getMedia(ChildEntry::MEDIA)->firstWhere('id', $media);
        abort_unless($file, 404);

        if ($entry->mediaCount() === 1 && blank($entry->description)) {
            throw ValidationException::withMessages([
                'file' => 'This memory has no words yet — add some before removing the last of it.',
            ]);
        }

        $file->delete();

        return ApiResponse::noContent();
    }
}
