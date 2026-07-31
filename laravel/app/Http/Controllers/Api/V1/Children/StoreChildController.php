<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Actions\Children\CreateChild;
use App\Data\ChildData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreChildRequest;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class StoreChildController extends Controller
{
    public function __invoke(StoreChildRequest $request, CreateChild $create): JsonResponse
    {
        $child = $create->handle($request->user(), ChildData::fromRequest($request));

        return ApiResponse::success(
            new ChildResource($child->load('memberships.user')),
            'The adventure is ready.',
            201,
        );
    }
}
