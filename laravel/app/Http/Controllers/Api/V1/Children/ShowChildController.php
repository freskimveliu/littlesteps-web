<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowChildController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        return ApiResponse::success(new ChildResource($child->load('memberships.user')));
    }
}
