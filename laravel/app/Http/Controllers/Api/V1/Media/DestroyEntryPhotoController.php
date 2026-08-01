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

class DestroyEntryPhotoController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry, int $media): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $file = $entry->getMedia(ChildEntry::PHOTOS)->firstWhere('id', $media);
        abort_unless($file, 404);

        if ($entry->photoCount() === 1 && blank($entry->description)) {
            throw ValidationException::withMessages([
                'photo' => 'This memory has no words yet — add some before removing its last photo.',
            ]);
        }

        $file->delete();

        return ApiResponse::noContent();
    }
}
