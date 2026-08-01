<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateChildRequest;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class UpdateChildController extends Controller
{
    public function __invoke(UpdateChildRequest $request, Child $child): JsonResponse
    {
        $this->authorize('update', $child);

        $child->update($request->safe()->except('photo'));

        if ($request->hasFile('photo')) {
            $child->addMediaFromRequest('photo')->toMediaCollection(Child::PHOTO);
        }

        return ApiResponse::success(
            new ChildResource($child->fresh()->load('memberships.user')),
            'Saved.',
        );
    }
}
