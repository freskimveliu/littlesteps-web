<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Actions\Children\CreateChild;
use App\Data\ChildData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreChildRequest;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class StoreChildController extends Controller
{
    public function __invoke(StoreChildRequest $request, CreateChild $create): JsonResponse
    {
        $child = $create->handle($request->user(), ChildData::fromRequest($request));

        if ($request->hasFile('photo')) {
            $child->addMediaFromRequest('photo')->toMediaCollection(Child::PHOTO);
        }

        return ApiResponse::success(
            new ChildResource($child->fresh()->load('memberships.user')),
            'The adventure is ready.',
            201,
        );
    }
}
