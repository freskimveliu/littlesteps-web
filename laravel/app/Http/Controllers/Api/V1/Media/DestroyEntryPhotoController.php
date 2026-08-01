<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestroyEntryPhotoController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildEntry $entry, int $media): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $file = $entry->getMedia(ChildEntry::PHOTOS)->firstWhere('id', $media);
        abort_unless($file, 404);

        $file->delete();

        return ApiResponse::noContent();
    }
}
