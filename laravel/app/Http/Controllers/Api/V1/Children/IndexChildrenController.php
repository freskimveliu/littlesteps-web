<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexChildrenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $children = $request->user()
            ->children()
            ->with(['memberships.user', 'media'])
            ->orderBy('birthday')
            ->get();

        return ApiResponse::success(ChildResource::collection($children));
    }
}
