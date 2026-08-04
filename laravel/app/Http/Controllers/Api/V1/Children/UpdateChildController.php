<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Actions\Children\UpdateChild;
use App\Data\ChildChangeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateChildRequest;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class UpdateChildController extends Controller
{
    public function __invoke(UpdateChildRequest $request, Child $child, UpdateChild $update): JsonResponse
    {
        $this->authorize('update', $child);

        $child = $update->handle($child, ChildChangeData::fromRequest($request));

        return ApiResponse::success(
            new ChildResource($child->load('memberships.user')),
            'Saved.',
        );
    }
}
