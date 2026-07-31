<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreChildPhotoController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('update', $child);

        $request->validate(['photo' => ['required', 'image', 'max:20480']]);

        $child->addMediaFromRequest('photo')->toMediaCollection(Child::PHOTO);

        return ApiResponse::success(new ChildResource($child->fresh()), 'Photo updated.');
    }
}
